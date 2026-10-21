<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigurationController extends Controller
{
    static string $page_title = 'MOE | Administration';

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    /**
     * What an account may be given. Roles are the ones in use plus the generic
     * fallback; permissions follow the vocabulary already stored on the column.
     */
    public const ROLES = [
        'administrator' => 'Administrator',
        'registry-officer' => 'Registry Officer',
        'user' => 'User',
    ];

    public const PERMISSIONS = [
        'create' => 'Create',
        'read' => 'Read',
        'update' => 'Update',
        'delete' => 'Delete',
    ];

    private function page(string $view, string $title, string $active_page, array $data = [])
    {
        return view($view, array_merge([
            'page_title' => self::$page_title . ' - ' . $title,
            'active_page' => $active_page,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version')
        ], $data));
    }

    public function index()
    {
        return $this->page('config/config', 'Settings', 'config');
    }

    public function usersRoles()
    {
        return $this->page('config/users-roles', 'Users, Roles & Permissions', 'users-roles', [
            'users' => User::with('staff')->orderBy('username')->paginate(15)->withQueryString(),
            // Only officers without an account: one account per officer.
            'staffOptions' => BasicInformation::query()
                ->whereNotIn('ulid', User::whereNotNull('basic_information_ulid')->pluck('basic_information_ulid'))
                ->orderBy('surname')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->staff_number})"])
                ->all(),
            'roles' => self::ROLES,
            'availablePermissions' => self::PERMISSIONS,
        ]);
    }

    /**
     * A dashboard account is opened for an officer already on the staff
     * register; only the credentials and what they may do are recorded here.
     */
    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid',
                                         'unique:users,basic_information_ulid'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(self::PERMISSIONS))],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'basic_information_ulid.unique' => 'That officer already has a dashboard account.',
            'basic_information_ulid.required' => 'Choose the officer this account belongs to.',
        ]);

        User::create([
            'basic_information_ulid' => $data['basic_information_ulid'],
            'username' => $data['username'],
            'email' => $data['email'],
            // Hashed by the cast on the model.
            'password' => $data['password'],
            'role' => $data['role'],
            'permissions' => json_encode($data['permissions'] ?? []),
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ]);

        return redirect()->route('users-roles')->with('status', 'Dashboard account created.');
    }

    /**
     * Deactivating is the way an account is withdrawn: the staff record and
     * everything attached to it stays put.
     */
    public function toggleUser(Request $request, User $user)
    {
        $user->update(['is_active' => $user->is_active ? 0 : 1]);

        return redirect()->route('users-roles')
            ->with('status', "Account {$user->username} " . ($user->is_active ? 'reactivated' : 'deactivated') . '.');
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
