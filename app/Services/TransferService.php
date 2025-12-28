<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Library\Sanitization\Sanitizer as S;
use App\Library\Validation\Validator as V;
use App\Storage\IdempotencyKeyStorage;
use App\Storage\TransactionStorage;
use App\Storage\WalletStorage;
use Illuminate\Support\Facades\DB;

class TransferService
{
    private ServiceUtil $ServiceUtil;

    private WalletStorage $WalletStorage;

    private TransactionStorage $TransactionStorage;

    private IdempotencyKeyStorage $IdempotencyKeyStorage;

    public function __construct(
        ServiceUtil $ServiceUtil,
        WalletStorage $WalletStorage,
        TransactionStorage $TransactionStorage,
        IdempotencyKeyStorage $IdempotencyKeyStorage
    ) {
        $this->ServiceUtil = $ServiceUtil;
        $this->WalletStorage = $WalletStorage;
        $this->TransactionStorage = $TransactionStorage;
        $this->IdempotencyKeyStorage = $IdempotencyKeyStorage;
    }

    public function createTransfer($idempotency_key, array $request)
    {
        // Sanitize
        @$from_wallet_id = S::value($request['from_wallet_id'])->digits();
        @$to_wallet_id = S::value($request['to_wallet_id'])->digits();
        @$transaction_amount = S::value($request['transaction_amount'])->string();
        @$transaction_description = S::value($request['transaction_description'])->string();

        if (empty($idempotency_key)) {
            throw new ApiException(400, 'Idempotency-Key header is required');
        }

        // Check idempotency key first
        $idempotency_endpoint = 'transfer:'.$from_wallet_id.':'.$to_wallet_id;
        $response = $this->IdempotencyKeyStorage->checkIdempotencyKey(
            $idempotency_key,
            $idempotency_endpoint,
            $request
        );
        if (! empty($response)) {
            return $response;
        }

        // Validate
        $Validator = V::create();

        $Validator->field('from_wallet_id', $from_wallet_id)
            ->required('Please enter from_wallet_id')
            ->digits('Please enter only numeric digits');

        $Validator->field('to_wallet_id', $to_wallet_id)
            ->required('Please enter to_wallet_id')
            ->digits('Please enter only numeric digits');

        $Validator->field('transaction_amount', $transaction_amount)
            ->required('Please enter transaction amount')
            ->digits('Please enter only numeric digits')
            ->greaterThanZero('Amount must be greater than 0');

        $Validator->field('transaction_description', $transaction_description)
            ->required('Please enter transaction description')
            ->maxLength(300);

        try {
            $Validator->validate();
        } catch (ApiException $e) {
            // Store idempotency for validation errors
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if self-transfer
        if ($from_wallet_id == $to_wallet_id) {
            $e = new ApiException(400, 'Cannot transfer to the same wallet');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if from wallet exists
        $from_wallet = $this->WalletStorage->getWalletById($from_wallet_id);

        if (empty($from_wallet)) {
            $e = new ApiException(404, 'Source wallet not found');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if to wallet exists
        $to_wallet = $this->WalletStorage->getWalletById($to_wallet_id);

        if (empty($to_wallet)) {
            $e = new ApiException(404, 'Target wallet not found');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if both wallets have the same currency
        if (strtolower($from_wallet['wallet_currency']) !== strtolower($to_wallet['wallet_currency'])) {
            $e = new ApiException(400, 'Cannot transfer between different currencies');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if sufficient balance in source wallet
        if ($from_wallet['wallet_balance'] < $transaction_amount) {
            $e = new ApiException(400, 'Insufficient balance');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Perform transfer in transaction
        DB::beginTransaction();

        try {
            // Debit from source wallet
            $this->WalletStorage->updateBalance($from_wallet_id, $transaction_amount, 'minus');
            $from_wallet = $this->WalletStorage->getWalletById($from_wallet_id);

            // Credit to target wallet
            $this->WalletStorage->updateBalance($to_wallet_id, $transaction_amount, 'plus');
            $to_wallet = $this->WalletStorage->getWalletById($to_wallet_id);

            // Insert debit transaction for source wallet
            $this->TransactionStorage->insertTransaction(
                $from_wallet_id,
                'transfer_debit',
                $transaction_amount,
                $from_wallet['wallet_balance'],
                $to_wallet_id,
                $idempotency_key,
                $transaction_description
            );

            // Insert credit transaction for target wallet
            $this->TransactionStorage->insertTransaction(
                $to_wallet_id,
                'transfer_credit',
                $transaction_amount,
                $to_wallet['wallet_balance'],
                $from_wallet_id,
                $idempotency_key,
                $transaction_description
            );

            DB::commit();

            $response_code = 200;
            $response_body = [
                'from_wallet_id' => $from_wallet['wallet_id'],
                'from_wallet_balance' => $from_wallet['wallet_balance'],
                'to_wallet_id' => $to_wallet['wallet_id'],
                'to_wallet_balance' => $to_wallet['wallet_balance'],
                'transaction_amount' => $transaction_amount,
            ];

            $response_body = $this->ServiceUtil->success($response_body, $response_code);

            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $response_code,
                $response_body
            );

            return $response_body;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
