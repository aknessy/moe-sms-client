<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfigurationController
{
    static string $page_title = 'MOE | Config';

    public function __construct(Request $request){

    }

    public function index(){
        return view('dashboard/config',
            [
                'page_title' => self::$page_title,
                'app_version' => config('app.app_version'),
            ]);
    }

    public function create(){
        
    }

    public function update(){

    }
}
