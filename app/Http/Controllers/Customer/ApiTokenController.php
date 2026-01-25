<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    /**
     * Generate a new API token for the authenticated customer.
     */
    public function generate(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        // Delete all existing tokens for this customer
        $customer->tokens()->delete();

        // Create new token
        $token = $customer->createToken('api-access-token', [
            'folder:import',
            'folder:read',
            'folder:write',
        ]);

        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'message' => 'API Token erfolgreich generiert',
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function revoke(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        // Delete all tokens
        $customer->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Token wurde widerrufen',
        ]);
    }

    /**
     * Check if customer has an active token.
     */
    public function status(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $hasToken = $customer->tokens()->count() > 0;

        return response()->json([
            'success' => true,
            'has_token' => $hasToken,
            'token_count' => $customer->tokens()->count(),
        ]);
    }
}
