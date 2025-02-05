<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    public function index(){
        return view('staff/index', [
            'page_title' => self::$page_title . ' - Staff',
            'app_version' => config('app.app_version')
        ]);
    }
}
