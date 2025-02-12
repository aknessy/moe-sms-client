<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    static string $page_title = 'MOE | Staff Documents';
    private string $uri;
    public function __construct(Request $request){
        $this->uri = $request->getRequestUri();
    }

    public function index($uuid){
        $uri_segment = explode('/', $this->uri);

        return view('docs/index', [
            'page_title' => self::$page_title,
            'active_page' => 'index',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function passport($uuid){
        $uri_segment = explode('/', $this->uri);

        return view('docs/passport', [
            'page_title' => self::$page_title,
            'active_page' => 'passport',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }
}
