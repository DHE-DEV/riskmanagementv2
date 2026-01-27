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

        // Delete only self-created and legacy tokens (preserve admin-created tokens)
        $customer->tokens()->where('name', 'like', 'self:%')->delete();
        $customer->tokens()->where('name', 'api-access-token')->delete();

        // Create new token with abilities based on customer settings
        $abilities = ['folder:import', 'folder:read', 'folder:write'];

        if ($customer->gtm_api_enabled) {
            $abilities[] = 'gtm:read';
        }

        $token = $customer->createToken('self:api-access-token', $abilities);

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

        // Delete only self-created and legacy tokens (preserve admin-created tokens)
        $customer->tokens()->where('name', 'like', 'self:%')->delete();
        $customer->tokens()->where('name', 'api-access-token')->delete();

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
        $selfTokenCount = $customer->tokens()
            ->where(function ($query) {
                $query->where('name', 'like', 'self:%')
                    ->orWhere('name', 'api-access-token');
            })
            ->count();

        $adminTokenCount = $customer->tokens()
            ->where('name', 'like', 'admin:%')
            ->count();

        return response()->json([
            'success' => true,
            'has_token' => ($selfTokenCount + $adminTokenCount) > 0,
            'token_count' => $selfTokenCount + $adminTokenCount,
            'self_token_count' => $selfTokenCount,
            'admin_token_count' => $adminTokenCount,
        ]);
    }
}
