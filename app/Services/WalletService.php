<?php

namespace App\Services;

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
        @$owner_name = S::value($request['owner_name'])->get();
        @$currency = S::value($request['currency'])->get();

        // Validate
        $Validator = V::create();

        $Validator->field('owner_name', $owner_name)
            ->required('Please enter owner name');

        $Validator->field('currency', $currency)
            ->required('Please enter currency')
            ->currencyCode();

        $Validator->validate();

        // Insert in db
        $wallet_id = $this->WalletStorage->insertWallet($owner_name, $currency);

        $response = [
            'wallet_id' => $wallet_id,
        ];

        return $this->ServiceUtil->success($response);
    }

    public function getWallet(array $request) {}

    public function listWallets(array $request) {}

    public function depositInWallet(array $request) {}

    public function withdrawFormWallet(array $request) {}

    public function walletBalance(array $request) {}

    public function walletTransactions(array $request) {}
}
