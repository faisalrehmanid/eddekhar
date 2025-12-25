<?php

use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Wallet routes
Route::prefix('wallets')->group(function () {
    // Create a new wallet
    Route::post('/', [WalletController::class, 'createWallet']);

    // Get wallet details
    Route::get('/{id}', [WalletController::class, 'getWallet']);

    // List all wallets
    Route::get('/', [WalletController::class, 'listWallets']);

    // Deposit funds
    Route::post('/{id}/deposit', [WalletController::class, 'depositInWallet']);

    // Withdraw funds
    Route::post('/{id}/withdraw', [WalletController::class, 'withdrawFormWallet']);

    // Get wallet balance
    Route::get('/{id}/balance', [WalletController::class, 'walletBalance']);

    // Get transaction history
    Route::get('/{id}/transactions', [WalletController::class, 'walletTransactions']);
});

// Transfer routes
Route::post('/transfers', [TransferController::class, 'createTransfer']);
