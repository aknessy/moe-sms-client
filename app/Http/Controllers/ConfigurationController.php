<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Permission;
use App\Models\Role;
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

    /** The permission vocabulary lives on the model that stores them. */
    public const PERMISSIONS = Permission::AVAILABLE;

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
            'users' => User::with(['staff', 'roleRecord'])->orderBy('username')->paginate(15)->withQueryString(),
            // Only officers without an account: one account per officer.
            'staffOptions' => BasicInformation::query()
                ->whereNotIn('ulid', User::whereNotNull('basic_information_ulid')->pluck('basic_information_ulid'))
                ->orderBy('surname')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->staff_number})"])
                ->all(),
            'roles' => Role::options(),
            'roleRecords' => $roles = Role::ordered()->load(['creator', 'permissions']),
            'availablePermissions' => self::PERMISSIONS,
            // What each role allows, so choosing one narrows the permissions
            // offered against the account without a round trip.
            'permissionsByRole' => $roles->mapWithKeys(
                fn ($role) => [$role->role => $role->permissionNames()]
            ),
        ]);
    }

    /**
     * Set what a role allows. The checkboxes carry the whole set, so what is
     * absent is withdrawn.
     */
    public function storeRolePermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(self::PERMISSIONS))],
        ]);

        $granted = $data['permissions'] ?? [];

        $role->grant($granted);
        $role->revoke(array_diff(array_keys(self::PERMISSIONS), $granted));

        // An account cannot keep a permission its role no longer allows.
        $withdrawn = $this->pruneAccountPermissions($role);

        return redirect()->route('users-roles')->with('status',
            trim("{$role->role} may now " . (($granted === []) ? 'do nothing.' : implode(', ', $granted) . '.')
                . ($withdrawn ? " Withdrew it from {$withdrawn} account(s) that no longer qualified." : '')));
    }

    /**
     * Strip from every account holding this role anything the role no longer
     * allows, and report how many were touched.
     */
    private function pruneAccountPermissions(Role $role): int
    {
        $allowed = $role->fresh()->permissionNames();
        $touched = 0;

        foreach ($role->users as $user) {
            $held = json_decode($user->permissions, true) ?: [];
            $kept = array_values(array_intersect($held, $allowed));

            if ($kept !== $held) {
                $user->update(['permissions' => json_encode($kept)]);
                $touched++;
            }
        }

        return $touched;
    }

    /**
     * A dashboard account is opened for an officer already on the staff
     * register; only the credentials and what they may do are recorded here.
     */
    public function storeUser(Request $request)
    {
        // An account may be given some of what its role allows, and nothing
        // beyond it.
        $allowed = Role::where('role', $request->input('role'))->first()?->permissionNames() ?? [];

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid',
                                         'unique:users,basic_information_ulid'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,role'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in($allowed)],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'basic_information_ulid.unique' => 'That officer already has a dashboard account.',
            'basic_information_ulid.required' => 'Choose the officer this account belongs to.',
            'permissions.*.in' => 'That permission is not one the chosen role allows.',
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
