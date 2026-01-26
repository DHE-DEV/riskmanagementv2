<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Folder\ProcessFolderImportJob;
use App\Models\Folder\FolderImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FolderImportController extends Controller
{
    /**
     * Enable detailed logging of folder imports
     */
    protected bool $detailedLogging;

    public function __construct()
    {
        $this->detailedLogging = config('services.folder_import.detailed_logging', false);
    }

    /**
     * Import folder data.
     */
    public function import(Request $request): JsonResponse
    {
        $customer = $request->user();

        // Log incoming request if detailed logging is enabled
        if ($this->detailedLogging) {
            Log::channel('folder_import')->info('FolderImport: INCOMING REQUEST', [
                'customer_id' => $customer?->id,
                'customer_email' => $customer?->email,
                'source' => $request->input('source'),
                'provider' => $request->input('provider'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->input('data'),
                'mapping_config' => $request->input('mapping_config'),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'source' => 'required|in:file,api,manual',
            'provider' => 'required|string|max:128',
            'data' => 'required|array',
            'mapping_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($this->detailedLogging) {
                Log::channel('folder_import')->warning('FolderImport: VALIDATION FAILED', [
                    'customer_id' => $customer?->id,
                    'errors' => $validator->errors()->toArray(),
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create import log
            $log = FolderImportLog::create([
                'customer_id' => $customer->id,
                'import_source' => $request->input('source'),
                'provider_name' => $request->input('provider'),
                'status' => 'pending',
                'source_data' => $request->input('data'),
                'mapping_config' => $request->input('mapping_config'),
            ]);

            if ($this->detailedLogging) {
                Log::channel('folder_import')->info('FolderImport: IMPORT LOG CREATED', [
                    'log_id' => $log->id,
                    'customer_id' => $customer->id,
                    'source' => $request->input('source'),
                    'provider' => $request->input('provider'),
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            // Dispatch background job
            ProcessFolderImportJob::dispatch($log->id);

            return response()->json([
                'success' => true,
                'message' => 'Import queued successfully',
                'log_id' => $log->id,
            ], 202);
        } catch (\Exception $e) {
            Log::channel('folder_import')->error('FolderImport: QUEUE FAILED', [
                'customer_id' => $customer?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String(),
            ]);

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
