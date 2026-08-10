<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards customer-owned API routes against tokens that do not belong to a customer.
 *
 * The customer global scope on folder data can only narrow queries when the request
 * acts on behalf of a customer. A token issued to any other model would leave the
 * scope inactive and return every customer's records, so those requests are refused
 * here rather than served unscoped.
 */
class EnsureCustomerApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint requires a customer API token.',
            ], 403);
        }

        return $next($request);
    }
}
