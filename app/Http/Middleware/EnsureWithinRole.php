<?php

namespace App\Http\Middleware;

use App\Support\Menu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an account inside the menu it was given.
 *
 * Hiding an entry only makes a page harder to find, not harder to reach — the
 * url still works, and an officer on the `User` role could read the whole
 * nominal roll by typing it. So the menu is the allow-list: whatever it points
 * at is permitted, and nothing else is.
 *
 * Accounts with the run of the system are not filtered at all, so adding a
 * page never means remembering to permit it here.
 */
class EnsureWithinRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = Menu::allowedRoutesFor($request->user());

        if ($allowed === null) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if (in_array($route, $allowed, true)) {
            return $next($request);
        }

        // The dashboard is every account's natural landing place, and the one
        // url they reach without choosing it — after signing in, or by typing
        // the bare address. Sending them to their own page reads better than
        // refusing them something they never asked for.
        if ($route === 'dashboard') {
            return redirect()->route(Menu::SELF_SERVICE_HOME);
        }

        abort(403, 'That part of the system is not open to your role.');
    }
}
