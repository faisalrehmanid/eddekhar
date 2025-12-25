<?php

namespace App\Services;

class WalletService
{
    private ServiceUtil $ServiceUtil;

    public function __construct(ServiceUtil $ServiceUtil)
    {
        $this->ServiceUtil = $ServiceUtil;
    }

    public function createWallet(array $request)
    {
        return $this->ServiceUtil->success(['wallet' => 'created']);
    }

    public function getWallet(array $request) {}

    public function listWallets(array $request) {}

    public function depositInWallet(array $request) {}

    public function withdrawFormWallet(array $request) {}

    public function walletBalance(array $request) {}

    public function walletTransactions(array $request) {}
}
