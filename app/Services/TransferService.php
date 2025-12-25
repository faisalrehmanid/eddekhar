<?php

namespace App\Services;

class TransferService
{
    private ServiceUtil $ServiceUtil;

    public function __construct(ServiceUtil $ServiceUtil)
    {
        $this->ServiceUtil = $ServiceUtil;
    }

    public function createTransfer(array $request)
    {
        return $this->ServiceUtil->success(['transfer' => 'created']);
    }
}
