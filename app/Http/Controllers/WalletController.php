<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wallet Controller
 *
 * Handles all wallet-related endpoints
 */
class WalletController extends Controller
{
    private WalletService $WalletService;

    public function __construct(WalletService $WalletService)
    {
        $this->WalletService = $WalletService;
    }

    public function createWallet(Request $request): JsonResponse
    {
        $response = $this->WalletService->createWallet($request->all());

        return response()->json($response, $response['code']);
    }

    public function getWallet($id, Request $request): JsonResponse
    {
        $response = $this->WalletService->getWallet($id);

        return response()->json($response, $response['code']);
    }

    public function listWallets(Request $request): JsonResponse
    {
        $response = $this->WalletService->listWallets($request->all());

        return response()->json($response, $response['code']);
    }

    public function depositInWallet($id, Request $request): JsonResponse
    {
        $idempotency_key = request()->header('Idempotency-Key');
        $response = $this->WalletService->depositInWallet($id, $idempotency_key, $request->all());

        return response()->json($response, $response['code']);
    }

    public function withdrawFormWallet($id, Request $request): JsonResponse
    {
        $idempotency_key = request()->header('Idempotency-Key');
        $response = $this->WalletService->withdrawFormWallet($id, $idempotency_key, $request->all());

        return response()->json($response, $response['code']);
    }

    public function walletBalance($id): JsonResponse
    {
        $response = $this->WalletService->walletBalance($id);

        return response()->json($response, $response['code']);
    }

    public function walletTransactions(Request $request): JsonResponse
    {
        $response = $this->WalletService->walletTransactions($request->all());

        return response()->json($response, $response['code']);
    }
}
