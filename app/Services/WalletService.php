<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Library\Sanitization\Sanitizer as S;
use App\Library\Validation\Validator as V;
use App\Storage\IdempotencyKeyStorage;
use App\Storage\TransactionStorage;
use App\Storage\WalletStorage;
use Illuminate\Support\Facades\DB;

class WalletService
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

    public function createWallet(array $request)
    {
        // Sanitize
        @$wallet_owner_name = S::value($request['wallet_owner_name'])->string();
        @$wallet_currency = S::value($request['wallet_currency'])->string();

        // Validate
        $Validator = V::create();

        $Validator->field('wallet_owner_name', $wallet_owner_name)
            ->required('Please enter owner name');

        $Validator->field('wallet_currency', $wallet_currency)
            ->required('Please enter wallet_currency')
            ->currencyCode();

        $Validator->validate();

        // Insert in db
        $wallet_id = $this->WalletStorage->insertWallet($wallet_owner_name, $wallet_currency);

        $data = [
            'wallet_id' => $wallet_id,
        ];

        return $this->ServiceUtil->success($data);
    }

    public function getWallet($wallet_id)
    {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->int();

        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            throw new ApiException(404, 'Wallet not found');
        }

        $data = $wallet;

        return $this->ServiceUtil->success($data);
    }

    public function listWallets(array $request)
    {
        $data = $this->WalletStorage->listWallets($request);

        return $this->ServiceUtil->success($data);
    }

    public function depositInWallet(
        $wallet_id,
        $idempotency_key,
        array $request
    ) {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->int();
        @$transaction_amount = S::value($request['transaction_amount'])->string();
        @$transaction_description = S::value($request['transaction_description'])->string();

        if (empty($idempotency_key)) {
            throw new ApiException(400, 'Idempotency-Key header is required');
        }

        // Check idempotency key first
        $idempotency_endpoint = 'deposit:'.$wallet_id;
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

        // Check if wallet exists
        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            $e = new ApiException(404, 'Wallet not found');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Perform deposit in transaction
        DB::beginTransaction();

        try {
            $this->WalletStorage->updateBalance($wallet_id, $transaction_amount, 'plus');
            $wallet = $this->WalletStorage->getWalletById($wallet_id);

            $this->TransactionStorage->insertTransaction(
                $wallet_id,
                'deposit',
                $transaction_amount,
                $wallet['wallet_balance'],
                null,
                $idempotency_key,
                $transaction_description
            );

            DB::commit();

            $response_code = 200;
            $response_body = [
                'wallet_id' => $wallet['wallet_id'],
                'wallet_balance' => $wallet['wallet_balance'],
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

    public function withdrawFormWallet(
        $wallet_id,
        $idempotency_key,
        array $request
    ) {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->int();
        @$transaction_amount = S::value($request['transaction_amount'])->string();
        @$transaction_description = S::value($request['transaction_description'])->string();

        if (empty($idempotency_key)) {
            throw new ApiException(400, 'Idempotency-Key header is required');
        }

        // Check idempotency key first
        $idempotency_endpoint = 'withdraw:'.$wallet_id;
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

        // Check if wallet exists
        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            $e = new ApiException(404, 'Wallet not found');
            $this->IdempotencyKeyStorage->insertIdempotencyKey(
                $idempotency_key,
                $idempotency_endpoint,
                $request,
                $e->getCode(),
                $e->toArray()
            );
            throw $e;
        }

        // Check if sufficient balance
        if ($wallet['wallet_balance'] < $transaction_amount) {
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

        // Perform withdrawal in transaction
        DB::beginTransaction();

        try {
            $this->WalletStorage->updateBalance($wallet_id, $transaction_amount, 'minus');
            $wallet = $this->WalletStorage->getWalletById($wallet_id);

            $this->TransactionStorage->insertTransaction(
                $wallet_id,
                'withdraw',
                $transaction_amount,
                $wallet['wallet_balance'],
                null,
                $idempotency_key,
                $transaction_description
            );

            DB::commit();

            $response_code = 200;
            $response_body = [
                'wallet_id' => $wallet['wallet_id'],
                'wallet_balance' => $wallet['wallet_balance'],
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

    public function walletBalance($wallet_id)
    {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->int();

        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            throw new ApiException(404, 'Wallet not found');
        }

        $data = ['wallet_balance' => $wallet['wallet_balance']];

        return $this->ServiceUtil->success($data);
    }

    public function listWalletTransactions($wallet_id, array $request)
    {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->int();

        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            throw new ApiException(404, 'Wallet not found');
        }

        $data = $this->TransactionStorage->listWalletTransactions($wallet_id, $request);

        return $this->ServiceUtil->success($data);
    }
}
