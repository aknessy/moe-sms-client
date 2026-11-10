<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Support\PromotionDue;
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
        return $this->page('config/config', 'Settings', 'config', [
            'organisation' => Setting::organisation(),
            'lowestGradeLevel' => PromotionDue::lowestGradeLevel(),
            'topGradeLevel' => BasicInformation::GRADE_LEVELS[1],
            'printing' => Setting::printing(),
        ]);
    }

    /**
     * The directorates and the desks inside them.
     *
     * Both are reference lists rather than free text: a department nobody
     * heads is one an officer's applications reach nobody from, and a unit
     * written in by hand belongs to no department at all.
     */
    public function departments()
    {
        // A department that has appeared on a staff record since this was
        // last opened still needs a head naming.
        Department::syncWithStaffRecords();

        return $this->page('config/departments', 'Departments & Units', 'departments', [
            'departments' => Department::ordered(),
            // Only the roles that head something; the rest are not offered.
            'hodRoles' => Role::ordered()
                ->filter(fn ($role) => str_starts_with($role->role, 'HOD '))
                ->pluck('role', 'role')
                ->all(),
        ]);
    }

    /** Add a unit to a department. */
    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:120',
                       // The same name may serve two departments; within one
                       // it is named once.
                       Rule::unique('units', 'name')
                           ->where('department_id', $request->input('department_id'))],
        ], [
            'department_id.required' => 'Choose the department the unit belongs to.',
            'name.required' => 'Give the unit a name.',
            'name.unique' => 'That department already has a unit by that name.',
        ]);

        Unit::create($data);

        return redirect()->route('departments')
            ->with('status', "{$data['name']} added.");
    }

    /**
     * Take a unit off. Officers are recorded against the unit's name rather
     * than against the row, so removing it leaves their record as it was and
     * only stops the unit being chosen again.
     */
    public function removeUnit(Unit $unit)
    {
        $name = $unit->name;

        $unit->delete();

        return redirect()->route('departments')
            ->with('status', "{$name} removed. Officers already recorded in it are unchanged.");
    }

    /**
     * The command's own details, which head every letter the system raises.
     */
    public function storeSettings(Request $request)
    {
        $data = $request->validate([
            'organisation_name' => ['required', 'string', 'max:160'],
            'organisation_command' => ['nullable', 'string', 'max:160'],
            'organisation_address' => ['nullable', 'string', 'max:400'],
            'organisation_telephone' => ['nullable', 'string', 'max:60'],
            'organisation_email' => ['nullable', 'email', 'max:160'],
            'organisation_commissioner_title' => ['required', 'string', 'max:160'],
        ], [
            'organisation_name.required' => 'Give the name this command writes under.',
            'organisation_commissioner_title.required' =>
                'Give the title an application is finally addressed to.',
        ]);

        Setting::putMany([
            'organisation.name' => $data['organisation_name'],
            'organisation.command' => $data['organisation_command'] ?? null,
            'organisation.address' => $data['organisation_address'] ?? null,
            'organisation.telephone' => $data['organisation_telephone'] ?? null,
            'organisation.email' => $data['organisation_email'] ?? null,
            'organisation.commissioner_title' => $data['organisation_commissioner_title'],
        ]);

        return redirect()->route('config')->with('status', 'Organisation details saved.');
    }

    /**
     * Rules the command sets rather than the code. At present, how far down
     * the grade levels a promotion exercise reaches.
     */
    public function storeRules(Request $request)
    {
        $data = $request->validate([
            'promotion_lowest_grade_level' => [
                'required', 'integer',
                'between:1,' . BasicInformation::GRADE_LEVELS[1],
            ],
        ], [
            'promotion_lowest_grade_level.required' =>
                'Give the lowest grade level considered for promotion.',
            'promotion_lowest_grade_level.between' =>
                'That is not a grade level on the scale.',
        ]);

        Setting::put('promotion.lowest_grade_level', (string) $data['promotion_lowest_grade_level']);

        return redirect()->route('config')->with('status', 'Promotion rules saved.');
    }

    /**
     * How the printable bio-data goes to paper — the sheet it is laid out on
     * and whether it stands or lies. The bio-data page reads these when it
     * sets its own print rules.
     */
    public function storePrinting(Request $request)
    {
        $data = $request->validate([
            'print_size' => ['required', Rule::in(['A3', 'A4'])],
            'print_orientation' => ['required', Rule::in(['Landscape', 'Portrait'])],
        ], [
            'print_size.in' => 'Choose a paper size the sheet is set to.',
            'print_orientation.in' => 'Choose an orientation the sheet is set to.',
        ]);

        Setting::putMany([
            'bio_data.print_size' => $data['print_size'],
            'bio_data.print_orientation' => $data['print_orientation'],
        ]);

        return redirect()->route('config')
            ->with('status', "Bio-data now prints on {$data['print_size']} paper, {$data['print_orientation']}.");
    }

    /**
     * Add a directorate. The staff wizard offers what is here and nothing
     * else, so a new one has to be recorded before anyone can be put in it.
     */
    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'department' => ['required', 'string', 'max:120', 'unique:departments,department'],
            'role' => ['nullable', Rule::in(Role::pluck('role')->all())],
        ], [
            'department.required' => 'Give the department a name.',
            'department.unique' => 'That department is already on the list.',
        ]);

        Department::create([
            'department' => $data['department'],
            'role' => $data['role'] ?: null,
        ]);

        return redirect()->route('departments')->with('status', "{$data['department']} added.");
    }

    /**
     * Which role heads which department. An application is routed by this, so
     * a department left unanswered simply goes to the administrators.
     */
    public function storeDepartmentHeads(Request $request)
    {
        $roles = Role::pluck('role')->all();

        $data = $request->validate([
            'heads' => ['nullable', 'array'],
            'heads.*' => ['nullable', Rule::in($roles)],
        ], [
            'heads.*.in' => 'That is not a role on the system.',
        ]);

        foreach ($data['heads'] ?? [] as $id => $role) {
            Department::where('id', $id)->update(['role' => $role ?: null]);
        }

        return redirect()->route('departments')->with('status', 'Department heads saved.');
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
