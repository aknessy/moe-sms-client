<?php

use App\Http\Middleware\EnsureUserIsAuthenticated;
use App\Http\Middleware\EnsureWithinRole;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.session' => EnsureUserIsAuthenticated::class,
            'guest' => RedirectIfAuthenticated::class,
            // Runs after the session is established, so it has an account to
            // read the role off.
            'within.role' => EnsureWithinRole::class,
        ]);

        // Anything the framework itself rejects as unauthenticated lands on login too.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
