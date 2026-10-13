<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\DeployInfo;
use Illuminate\Http\JsonResponse;

/**
 * GET /v1/app-info – Stand des Servers (Branch, Commit, Deploy-Zeit), damit
 * Apps in ihrer App-Info zeigen koennen, gegen welchen Stand sie laufen.
 */
class AppInfoController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['server' => DeployInfo::get()]]);
    }
}
