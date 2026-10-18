<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a signed-in user off the login screen.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
