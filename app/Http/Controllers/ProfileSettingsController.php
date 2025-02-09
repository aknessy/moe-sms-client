<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
class ProfileSettingsController
{
    static string $page_title = 'MOE | Staff Management';

    public function index(Request $request){
        $resources = Resource::all();

        return view('dashboard/profile-settings',
            [
                'page_title' => self::$page_title . ' - Dashboard',
                'app_version' => config('app.app_version'),
                'resources' => $resources,
            ]);
    }

    public function changePassword(Request $request){
        return view('dashboard/change-password',
        [
            'page_title' => self::$page_title . ' - Change Password',
            'app_version' => config('app.app_version'),
        ]); 
    }
}
