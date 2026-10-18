<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HumanResourceController extends Controller
{
    static string $page_title = 'MOE | Human Resources';

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

    public function leave()
    {
        return $this->page('human-resources/leave-management', 'Leave Management', 'leave-management');
    }

    public function promotions()
    {
        return $this->page('human-resources/promotion-exercises', 'Promotion Exercises', 'promotion-exercises');
    }

    public function appraisal()
    {
        return $this->page('human-resources/performance-appraisal', 'Performance Appraisal', 'performance-appraisal');
    }

    public function recruitment()
    {
        return $this->page('human-resources/recruitment', 'Recruitment', 'recruitment');
    }

    public function training()
    {
        return $this->page('human-resources/training', 'Training & Capacity Building', 'training');
    }

    public function establishment()
    {
        return $this->page('human-resources/establishment-strength', 'Establishment Strength', 'establishment-strength');
    }
}
