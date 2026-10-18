<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    static string $page_title = 'MOE | Administration';

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
        return $this->page('config/config', 'Settings', 'config');
    }

    public function usersRoles()
    {
        return $this->page('config/users-roles', 'Users, Roles & Permissions', 'users-roles');
    }

    public function auditTrail()
    {
        return $this->page('config/audit-trail', 'Audit Trail', 'audit-trail');
    }

    public function referenceData()
    {
        return $this->page('config/reference-data', 'Reference Data', 'reference-data');
    }

    public function notificationTemplates()
    {
        return $this->page('config/notification-templates', 'Notification Templates', 'notification-templates');
    }

    public function backupData()
    {
        return $this->page('config/backup-data', 'Backup & Data', 'backup-data');
    }

    public function create()
    {
    }

    public function update()
    {
    }
}
