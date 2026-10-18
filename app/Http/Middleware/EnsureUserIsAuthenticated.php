<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every non-public route behind a valid session.
 *
 * On top of the session guard itself this re-checks that the account is still
 * active on each request, so disabling a user takes effect immediately rather
 * than whenever their session happens to expire.
 */
class EnsureUserIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $this->reject($request, 'Please sign in to continue.');
        }

        if (Auth::user()->is_active === 0 || Auth::user()->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->reject($request, 'This account has been deactivated.');
        }

        // Keeps the idle timeout honest: every authenticated request pushes the
        // session's expiry forward, and the id is rotated on login/logout only.
        $request->session()->put('last_active_at', now()->timestamp);

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        // Remember where they were headed so login can send them back.
        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('login')->withErrors(['login' => $message]);
    }
}
