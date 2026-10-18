<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceAndAccountController extends Controller
{
    static string $page_title = 'MOE | Finance & Account';

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

    public function salaryStructure()
    {
        return $this->page('finance-account/salary-structure', 'Salary Structure', 'salary-structure');
    }

    public function allowances()
    {
        return $this->page('finance-account/allowances', 'Allowances', 'allowances');
    }

    public function loansAdvances()
    {
        return $this->page('finance-account/loans-advances', 'Loans & Advances', 'loans-advances');
    }

    public function arrearsVariations()
    {
        return $this->page('finance-account/arrears-variations', 'Arrears & Variations', 'arrears-variations');
    }

    public function remittances()
    {
        return $this->page('finance-account/remittances', 'Remittances', 'remittances');
    }

    public function deductions()
    {
        $uri_segment = explode('/', $this->uri);

        return view('finance-account/deductions', [
            'page_title' => self::$page_title . ' - Staff Deductions',
            'active_page' => 'deductions',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function payroll()
    {
        $uri_segment = explode('/', $this->uri);

        return view('finance-account/payroll', [
            'page_title' => self::$page_title . ' - Pay Roll',
            'active_page' => 'payroll',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function payslip()
    {
        $uri_segment = explode('/', $this->uri);

        return view('finance-account/payslip', [
            'page_title' => self::$page_title . ' - Pay Slip',
            'active_page' => 'payslip',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }
}
