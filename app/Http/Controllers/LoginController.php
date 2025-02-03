<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController
{
    static $page_title = 'MOE | Staff Management';

    public function index()
    {
        return view('layouts/login', ['page_title' => self::$page_title]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('userhandle', 'secrethash');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('dashboard');
        }

        return redirect('login')->with('error', 'Invalid credentials');
    }
}
