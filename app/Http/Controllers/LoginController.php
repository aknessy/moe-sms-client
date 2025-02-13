<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
        $input_fields = $request->validate([
            'userhandle' => 'required|string|exists:users,name',
            'secrethash' => 'required|string'
        ]);

        $credentials = [
            'name' => $request->input('userhandle'),
            'password' => $request->input('secrethash')
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['login' => 'Invalid credentials'])->onlyInput('userhandle');
    }
}
