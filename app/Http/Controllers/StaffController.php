<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    public function index(){
        return view('staff/index', [
            'page_title' => self::$page_title . ' - Staff',
            'active_page' => 'index',
            'app_version' => config('app.app_version')
        ]);
    }

    public function edu_progress(){
        return view('staff/edu_progress', [
            'page_title' => self::$page_title . ' - Edu Progress',
            'active_page' => 'edu-progress',
            'app_version' => config('app.app_version')
        ]);
    }

    public function promotions(){
        return view('staff/promotions', [
            'page_title' => self::$page_title . ' - Promotions',
            'active_page' => 'promotion',
            'app_version' => config('app.app_version')
        ]);
    }

    public function leave(Request $request){
        return view('staff/leave', [
            'page_title' => self::$page_title . ' - Leave',
            'active_page' => 'leave',
            'app_version' => config('app.app_version')
        ]);
    }
}
