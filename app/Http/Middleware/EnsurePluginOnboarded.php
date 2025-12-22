<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePluginOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer && !$customer->hasPluginClient()) {
            // Don't redirect if already on onboarding page
            if (!$request->is('plugin/onboarding*')) {
                return redirect()->route('plugin.onboarding');
            }
        }

        return $next($request);
    }
}
