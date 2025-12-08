<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShareLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShareLinkController extends Controller
{
    /**
     * Store a new share link
     * POST /api/v1/share-links?save_to_database=true
     */
    public function store(Request $request): JsonResponse
    {
        $saveToDatabase = filter_var($request->query('save_to_database', true), FILTER_VALIDATE_BOOLEAN);

        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['type', 'title', 'expires_in_days', 'save_to_database']);

        if ($saveToDatabase) {
            $expiresAt = null;
            if ($request->has('expires_in_days')) {
                $expiresAt = now()->addDays($request->input('expires_in_days'));
            }

            $shareLink = ShareLink::create([
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'data' => $data,
                'expires_at' => $expiresAt,
                'created_by_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Share link created successfully',
                'data' => [
                    'id' => $shareLink->id,
                    'token' => $shareLink->token,
                    'share_url' => $shareLink->getShareUrl(),
                    'api_url' => url("/api/v1/share-links/{$shareLink->token}"),
                    'expires_at' => $shareLink->expires_at?->toIso8601String(),
                    'created_at' => $shareLink->created_at->toIso8601String(),
                ],
            ], 201);
        }

        // Temporary link without database storage (base64 encoded)
        $encodedData = base64_encode(json_encode($data));
        $tempToken = 'temp_' . substr(md5($encodedData . time()), 0, 16);

        return response()->json([
            'success' => true,
            'message' => 'Temporary share link created (not stored in database)',
            'data' => [
                'token' => $tempToken,
                'encoded_data' => $encodedData,
                'note' => 'This link is not stored. Use save_to_database=true for persistent links.',
            ],
        ], 200);
    }

    /**
     * Get share link by token
     * GET /api/v1/share-links/{token}
     */
    public function show(string $token): JsonResponse
    {
        $shareLink = ShareLink::where('token', $token)->first();

        if (!$shareLink) {
            return response()->json([
                'success' => false,
                'message' => 'Share link not found',
            ], 404);
        }

        if ($shareLink->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Share link has expired',
            ], 410);
        }

        $shareLink->incrementViews();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $shareLink->id,
                'token' => $shareLink->token,
                'type' => $shareLink->type,
                'title' => $shareLink->title,
                'data' => $shareLink->data,
                'views' => $shareLink->views,
                'expires_at' => $shareLink->expires_at?->toIso8601String(),
                'created_at' => $shareLink->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete share link
     * DELETE /api/v1/share-links/{token}
     */
    public function destroy(string $token): JsonResponse
    {
        $shareLink = ShareLink::where('token', $token)->first();

        if (!$shareLink) {
            return response()->json([
                'success' => false,
                'message' => 'Share link not found',
            ], 404);
        }

        $shareLink->delete();

        return response()->json([
            'success' => true,
            'message' => 'Share link deleted successfully',
        ]);
    }

    /**
     * List all share links (with pagination)
     * GET /api/v1/share-links
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShareLink::query();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->boolean('active_only', false)) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        }

        $shareLinks = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $shareLinks->items(),
            'meta' => [
                'current_page' => $shareLinks->currentPage(),
                'last_page' => $shareLinks->lastPage(),
                'per_page' => $shareLinks->perPage(),
                'total' => $shareLinks->total(),
            ],
        ]);
    }
}
