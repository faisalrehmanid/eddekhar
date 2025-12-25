<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wallet Controller
 *
 * Handles all wallet-related endpoints
 */
class WalletController extends Controller
{
    public function createWallet(Request $request): JsonResponse {}

    public function getWallet(int $id): JsonResponse {}

    public function listWallets(Request $request): JsonResponse {}

    public function depositInWallet(Request $request, int $id): JsonResponse {}

    public function withdrawFormWallet(Request $request, int $id): JsonResponse {}

    public function walletBalance(int $id): JsonResponse {}

    public function walletTransactions(Request $request, int $id): JsonResponse {}
}
