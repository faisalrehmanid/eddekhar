<?php

namespace App\Services;

use App\Library\Sanitization\Sanitizer as S;
use App\Library\Validation\Validator as V;

class WalletService
{
    private ServiceUtil $ServiceUtil;

    public function __construct(ServiceUtil $ServiceUtil)
    {
        $this->ServiceUtil = $ServiceUtil;
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
            ->required('Please enter currency');

        $Validator->validate();

        return $this->ServiceUtil->success(['wallet' => 'created']);
    }

    public function getWallet(array $request) {}

    public function listWallets(array $request) {}

    public function depositInWallet(array $request) {}

    public function withdrawFormWallet(array $request) {}

    public function walletBalance(array $request) {}

    public function walletTransactions(array $request) {}
}
