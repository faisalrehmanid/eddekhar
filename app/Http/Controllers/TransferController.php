<?php

namespace App\Http\Controllers;

use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Transfer Controller
 *
 * Handles transfer operations between wallets
 */
class TransferController extends Controller
{
    private TransferService $TransferService;

    public function __construct(TransferService $TransferService)
    {
        $this->TransferService = $TransferService;
    }

    public function createTransfer(Request $request): JsonResponse
    {
        $response = $this->TransferService->createTransfer($request->all());

        return response()->json($response, $response['code']);
    }
}
