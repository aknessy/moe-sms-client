<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
class ProfileSettingsController extends Controller
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
}
