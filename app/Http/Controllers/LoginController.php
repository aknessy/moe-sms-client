<?php

namespace App\Http\Controllers;

use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController
{
    static $page_title = 'MOE | Staff Management';

    public function index()
    {
        return view('login/index', [
            'page_title' => self::$page_title . ' - Login',
            'app_version' => config('app.app_version')
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'userhandle' => 'required|string',
            'secrethash' => 'required|string',
        ], [], [
            'userhandle' => 'username',
            'secrethash' => 'password',
        ]);

        // The accounts table keys on `username`, and only active accounts may sign in.
        $credentials = [
            'username' => $request->input('userhandle'),
            'password' => $request->input('secrethash'),
            'is_active' => 1,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'Those credentials do not match our records.',
            ])->redirectTo(route('login'));
        }

        // New session id on privilege change, to close off session fixation.
        $request->session()->regenerate();

        // Where they were heading before they were sent here — but only if it
        // is somewhere their role may go. An officer bounced to the login from
        // an administrative page would otherwise sign in straight into a 403.
        $intended = $request->session()->pull('url.intended');

        return redirect()->to(Menu::allows($request->user(), $intended)
            ? $intended
            : route(Menu::homeFor($request->user())));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
