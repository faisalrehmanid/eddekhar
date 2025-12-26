<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Library\Sanitization\Sanitizer as S;
use App\Library\Validation\Validator as V;
use App\Storage\TransactionStorage;
use App\Storage\WalletStorage;

class WalletService
{
    private ServiceUtil $ServiceUtil;

    private WalletStorage $WalletStorage;

    private TransactionStorage $TransactionStorage;

    public function __construct(
        ServiceUtil $ServiceUtil,
        WalletStorage $WalletStorage,
        TransactionStorage $TransactionStorage
    ) {
        $this->ServiceUtil = $ServiceUtil;
        $this->WalletStorage = $WalletStorage;
        $this->TransactionStorage = $TransactionStorage;
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
        @$wallet_id = S::value($wallet_id)->digits();

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

    public function depositInWallet($wallet_id, array $request)
    {
        // Sanitize
        @$wallet_id = S::value($wallet_id)->digits();
        @$transaction_amount = S::value($request['transaction_amount'])->string();
        @$transaction_description = S::value($request['transaction_description'])->string();
        $idempotencyKey = '1234-8765';

        // Validate wallet details
        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        if (empty($wallet)) {
            throw new ApiException(404, 'Wallet not found');
        }

        // Validate
        $Validator = V::create();

        $Validator->field('transaction_amount', $transaction_amount)
            ->required('Please enter transaction amount')
            ->digits()
            ->greaterThanZero();

        $Validator->field('transaction_description', $transaction_description)
            ->required('Please enter transaction description')
            ->maxLength(300);

        $Validator->validate();

        $this->WalletStorage->updateBalance($wallet_id, $transaction_amount);
        $wallet = $this->WalletStorage->getWalletById($wallet_id);

        $this->TransactionStorage->insertTransaction(
            $wallet_id,
            'deposit',
            $transaction_amount,
            $wallet['wallet_balance'],
            null,
            $idempotencyKey,
            $transaction_description
        );

        $data = [
            'wallet_id' => $wallet['wallet_id'],
            'wallet_balance' => $wallet['wallet_balance'],
            'transaction_amount' => $transaction_amount,
        ];

        return $this->ServiceUtil->success($data);
    }

    public function withdrawFormWallet(array $request) {}

    public function walletBalance(array $request) {}

    public function walletTransactions(array $request) {}
}
