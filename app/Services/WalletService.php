<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Library\Sanitization\Sanitizer as S;
use App\Library\Validation\Validator as V;
use App\Storage\WalletStorage;

class WalletService
{
    private ServiceUtil $ServiceUtil;

    private WalletStorage $WalletStorage;

    public function __construct(
        ServiceUtil $ServiceUtil,
        WalletStorage $WalletStorage
    ) {
        $this->ServiceUtil = $ServiceUtil;
        $this->WalletStorage = $WalletStorage;
    }

    public function createWallet(array $request)
    {
        // Sanitize
        @$wallet_owner_name = S::value($request['wallet_owner_name'])->get();
        @$wallet_currency = S::value($request['wallet_currency'])->get();

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

    public function listWallets(array $request) {}

    public function depositInWallet(array $request) {}

    public function withdrawFormWallet(array $request) {}

    public function walletBalance(array $request) {}

    public function walletTransactions(array $request) {}
}
