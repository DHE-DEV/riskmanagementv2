<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Folder\ProcessFolderImportJob;
use App\Models\Folder\FolderImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FolderImportController extends Controller
{
    /**
     * Import folder data.
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|in:file,api,manual',
            'provider' => 'required|string|max:128',
            'data' => 'required|array',
            'mapping_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get authenticated user (Customer model via Sanctum token)
            $customer = $request->user();

            // Create import log
            $log = FolderImportLog::create([
                'customer_id' => $customer->id,
                'import_source' => $request->input('source'),
                'provider_name' => $request->input('provider'),
                'status' => 'pending',
                'source_data' => $request->input('data'),
                'mapping_config' => $request->input('mapping_config'),
            ]);

            // Dispatch background job
            ProcessFolderImportJob::dispatch($log->id);

            return response()->json([
                'success' => true,
                'message' => 'Import queued successfully',
                'log_id' => $log->id,
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue import',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import log status.
     */
    public function getImportStatus(string $logId): JsonResponse
    {
        try {
            $log = FolderImportLog::findOrFail($logId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $log->id,
                    'status' => $log->status,
                    'folder_id' => $log->folder_id,
                    'records_imported' => $log->records_imported,
                    'records_failed' => $log->records_failed,
                    'error_message' => $log->error_message,
                    'started_at' => $log->started_at,
                    'completed_at' => $log->completed_at,
                    'duration_seconds' => $log->duration,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import log not found',
            ], 404);
        }
    }

    /**
     * List import logs for the authenticated customer.
     */
    public function listImports(Request $request): JsonResponse
    {
        try {
            $logs = FolderImportLog::with('folder:id,folder_number,folder_name')
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch import logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
