<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    static $page_title = 'MOE | Staff Management';

    public function index()
    {
        return view('dashboard/index', [
            'page_title' => self::$page_title . ' - Dashboard',
            'app_version' => config('app.app_version')
        ]);
    }
}
