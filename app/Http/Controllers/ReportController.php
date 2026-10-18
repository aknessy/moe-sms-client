<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    static string $page_title = 'MOE | Reports & Analytics';

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

    public function nominalRoll()
    {
        return $this->page('reports/nominal-roll-report', 'Nominal Roll Report', 'nominal-roll-report');
    }

    public function retirementProjection()
    {
        return $this->page('reports/retirement-projection', 'Retirement Projection', 'retirement-projection');
    }

    public function payroll()
    {
        return $this->page('reports/payroll-report', 'Payroll Report', 'payroll-report');
    }

    public function headcount()
    {
        return $this->page('reports/headcount-report', 'Headcount & Audit', 'headcount-report');
    }
}
