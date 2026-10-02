<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zugang zum neuen Admin-Bereich (/adminv2).
 *
 * Gleiche Regel wie im Filament-Panel (User::canAccessPanel): angemeldet ueber
 * den web-Guard, Administrator und aktiv. Beide Bereiche teilen sich damit
 * die Anmeldung – wer in /admin eingeloggt ist, ist es auch hier.
 */
class EnsureAdminV2Access
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return redirect()->guest(route('adminv2.login'));
        }

        abort_unless($user->is_admin && $user->is_active, 403);

        // Die Admin-Oberflaeche ist deutsch. SetEventLocale stellt die Sprache
        // sonst nach Browser/Cookie um, und $event->title folgt der App-Locale.
        app()->setLocale(config('app.locale'));

        return $next($request);
    }
}
