<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    private string $uri;
    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    public function index(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/index', [
            'page_title' => self::$page_title . ' - Staff',
            'active_page' => 'index',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function edu_progress(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/edu_progress', [
            'page_title' => self::$page_title . ' - Edu Progress',
            'active_page' => 'edu-progress',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function promotions(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/promotions', [
            'page_title' => self::$page_title . ' - Promotions',
            'active_page' => 'promotion',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function leave(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/leave', [
            'page_title' => self::$page_title . ' - Leave',
            'active_page' => 'leave-record',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function next_of_kin(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/next_of_kin', [
            'page_title' => self::$page_title . ' - Next of Kin',
            'active_page' => 'next-of-kin',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function referee(){
        $uri_segment = explode('/', $this->uri);
        return view('staff/referees', [
            'page_title' => self::$page_title . ' - Referees',
            'active_page' => 'referee',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function career(){
        $uri_segment = explode('/', $this->uri);
        return view('staff/work_career', [
            'page_title' => self::$page_title . ' - Work Career',
            'active_page' => 'career',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }
}
