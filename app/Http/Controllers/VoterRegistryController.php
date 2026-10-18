<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VoterRegistryController extends Controller
{
    static string $page_title = 'MOE | ICT/Voter Registry';

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    private function page(string $view, string $title, string $active_page)
    {
        return view($view, [
            'page_title' => self::$page_title . ' - ' . $title,
            'active_page' => $active_page,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version')
        ]);
    }

    public function index()
    {
        return $this->page('ict/index', 'Overview', 'index');
    }

    public function adhocStaff()
    {
        return $this->page('ict/adhoc-staff', 'Ad-hoc Staff', 'adhoc-staff');
    }

    public function voterRegistration()
    {
        return $this->page('ict/voter-registration', 'Voter Registration', 'voter-registration');
    }

    public function pollingUnits()
    {
        return $this->page('ict/polling-units', 'Polling Units', 'polling-units');
    }

    public function deviceRegister()
    {
        return $this->page('ict/device-register', 'Device & Asset Register', 'device-register');
    }
}
