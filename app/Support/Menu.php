<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * The sidebar, as data.
 *
 * It used to live inside the sidebar component, which was fine while everyone
 * saw the same thing. They do not: an officer on the `User` role has no
 * business in the Registry or in Administration, and the menu is also what
 * says which pages they may reach at all — see EnsureWithinRole, which reads
 * its allow-list straight off here. One list, so what is shown and what is
 * permitted cannot drift apart.
 *
 * Each entry names a route, and optionally the sibling routes that belong to
 * the same page — a form's POST, an export, a wizard's later steps. Those are
 * `also`: not shown, but reachable, and highlighted as their parent.
 */
class Menu
{
    /**
     * Roles that see only their own corner of the system. Every other role
     * sees the whole menu; their remits will be narrowed when they are
     * decided.
     */
    public const SELF_SERVICE_ROLES = ['User'];

    /** Reachable by anyone signed in, menu or no menu. */
    public const ALWAYS_ALLOWED = [
        'logout', 'storage.local', 'docs', 'passport',
        'profile-settings', 'change-password', 'change-password.store',
        // A notice is addressed to an account, so there is nothing in here
        // that is not already theirs.
        'notifications', 'notifications.open', 'notifications.read-all',
        // Reached from the navbar's Options menu rather than a sidebar, so it
        // belongs to no role's menu in particular.
        'my-photo', 'my-photo.store', 'my-photo.show',
    ];

    /** Where a self-service officer lands, and is sent back to. */
    public const SELF_SERVICE_HOME = 'my-bio-data';

    public static function isSelfService(?User $user): bool
    {
        return in_array($user?->role, self::SELF_SERVICE_ROLES, true);
    }

    /** The page this account lands on when it has asked for nothing else. */
    public static function homeFor(?User $user): string
    {
        return self::isSelfService($user) ? self::SELF_SERVICE_HOME : 'dashboard';
    }

    /**
     * May this account open that url?
     *
     * Asked of the page someone was heading for before they were sent to the
     * login — following them to it afterwards is only a kindness if it is a
     * page they may actually see, and a wall of 403 the moment they sign in
     * is not.
     */
    public static function allows(?User $user, ?string $url): bool
    {
        // No url, nothing to follow — asked before the role is looked at, so
        // this answers the same way whoever is signing in.
        if (blank($url)) {
            return false;
        }

        $allowed = self::allowedRoutesFor($user);
        $denied = self::deniedRoutesFor($user);

        if ($allowed === null && $denied === []) {
            return true;
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
        } catch (\Throwable) {
            // Nothing answers to it, so there is nothing to follow.
            return false;
        }

        if (in_array($route->getName(), $denied, true)) {
            return false;
        }

        return $allowed === null || in_array($route->getName(), $allowed, true);
    }

    /**
     * The Finance/Account pages HOD GAP does not work in: everything under it
     * but what their own menu lists.
     */
    public const HOD_GAP_DENIED = [
        'salary-structure', 'payroll', 'arrears-variations', 'remittances',
        // Nothing under ICT/Voter Registry is theirs.
        'voter-registry', 'adhoc-staff', 'voter-registration', 'polling-units', 'device-register',
    ];

    /**
     * Routes this account may not reach even though its menu is otherwise
     * the full one: what only the Administrator runs, and for the GAP desk,
     * the Finance and ICT pages that are not theirs.
     */
    public static function deniedRoutesFor(?User $user): array
    {
        $denied = self::isAdministrator($user) ? [] : self::ADMINISTRATOR_ONLY;

        if (ReportingAuthority::atGapDesk($user)) {
            $denied = array_merge($denied, self::HOD_GAP_DENIED);
        }

        return $denied;
    }

    /**
     * What only the Administrator runs: the system's settings, its accounts
     * and roles, its notification templates, and its backups. Everyone else —
     * HOD GAP, Registry Officers, the other heads of department, Electoral
     * Officers — is kept out of them, by menu and by url alike.
     */
    public const ADMINISTRATOR_ONLY = [
        'config', 'config.organisation', 'config.rules', 'config.printing',
        'users-roles', 'users-roles.store', 'users-roles.toggle', 'users-roles.permissions',
        'notification-templates',
        'backup-data',
    ];

    public static function isAdministrator(?User $user): bool
    {
        return $user?->role === User::ADMIN_ROLE;
    }

    /** The menu without what only the Administrator may open. */
    private static function withoutAdministratorOnly(array $menu): array
    {
        foreach ($menu as &$group) {
            if (isset($group['children'])) {
                $group['children'] = array_values(array_filter(
                    $group['children'],
                    fn ($child) => ! in_array($child['route'] ?? null, self::ADMINISTRATOR_ONLY, true),
                ));
            }
        }
        unset($group);

        return $menu;
    }

    /** The menu this account should see. */
    public static function forUser(?User $user): array
    {
        if (self::isSelfService($user)) {
            return self::selfService();
        }

        if (ReportingAuthority::atGapDesk($user)) {
            return self::withoutAdministratorOnly(
                self::forHodGap(self::full(), ReportingAuthority::isHodGap($user)));
        }

        $menu = self::full();

        // The head of any other department builds that department's roster,
        // so it is on their sidebar under Human Resources.
        if (ReportingAuthority::headsOwnDepartment($user)) {
            foreach ($menu as &$group) {
                if ($group['label'] === 'Human Resources') {
                    $group['children'][] = ['label' => 'Leave Roster', 'route' => 'leave-roster',
                                            'also' => ['leave-roster.store', 'leave-roster.remove']];
                }
            }
            unset($group);
        }

        return self::isAdministrator($user) ? $menu : self::withoutAdministratorOnly($menu);
    }

    /**
     * HOD GAP acts on every officer's appraisal, so it sits on their sidebar
     * in its own right, straight under the Dashboard, rather than inside
     * Human Resources — and is taken out of there so it is not listed twice.
     */
    private static function forHodGap(array $menu, bool $isHead = true): array
    {
        foreach ($menu as &$group) {
            if (isset($group['children'])) {
                $group['children'] = array_values(array_filter(
                    $group['children'],
                    fn ($child) => ($child['route'] ?? null) !== 'hr-appraisal',
                ));
            }
        }
        unset($group);

        // Finance/Account is cut down to what HOD GAP deals with, and the
        // ICT/Voter Registry is not theirs at all.
        $menu = array_values(array_filter($menu, fn ($group) => $group['label'] !== 'ICT/Voter Registry'));

        foreach ($menu as &$group) {
            if ($group['label'] === 'Finance/Account') {
                $group['children'] = [
                    // There is no separate request page yet; it opens the
                    // Pay Slip page.
                    ['label' => 'Pay Slip Request', 'route' => 'payslip'],
                    ['label' => 'Loans & Advances', 'route' => 'loans-advances'],
                    ['label' => 'Deductions', 'route' => 'staff-deductions'],
                    ['label' => 'Allowances', 'route' => 'allowances'],
                ];
            }
        }
        unset($group);

        $appraisal = [
            'label' => 'Performance Appraisal',
            'icon' => 'chart',
            'route' => 'hr-appraisal',
            'also' => ['hr-appraisal.bio-data', 'hr-appraisal.career-history',
                       'hr-appraisal.qualifications', 'hr-appraisal.self-reporting',
                       'hr-appraisal.character-traits', 'hr-appraisal.commendations',
                       'hr-appraisal.sanctions', 'hr-appraisal.leadership-traits',
                       'hr-appraisal.general-remarks', 'hr-appraisal.suggestions',
                       'hr-appraisal.staff-declaration',
                       'hr-appraisal.reporting-officer-declaration',
                       'hr-appraisal.counter-signing-officer-report'],
        ];

        $added = [$appraisal, [
            'label' => 'Leave Roster',
            'icon' => 'users',
            'route' => 'leave-roster',
            'also' => ['leave-roster.store', 'leave-roster.remove'],
        ]];

        // Designating a Registry Officer is the head's alone; the officers
        // they designate do not designate others.
        if ($isHead) {
            $added[] = [
                'label' => 'Registry Officer Delegates',
                'icon' => 'users',
                'route' => 'gap-delegates',
                'also' => ['gap-delegates.store', 'gap-delegates.revoke'],
            ];
        }

        array_splice($menu, 1, 0, $added);

        return $menu;
    }

    /**
     * Every route name the account may reach: what its menu points at, the
     * sibling routes that belong to those pages, and the handful that are open
     * to anyone signed in.
     *
     * Null means no restriction — the account sees everything, so listing it
     * would only be a second place to forget to update.
     */
    public static function allowedRoutesFor(?User $user): ?array
    {
        if (! self::isSelfService($user)) {
            return null;
        }

        return array_values(array_unique(array_merge(
            self::routeNames(self::forUser($user)),
            self::ALWAYS_ALLOWED,
        )));
    }

    /** Every route name a menu reaches, `also` entries included. */
    public static function routeNames(array $menu): array
    {
        $names = [];

        foreach ($menu as $group) {
            foreach (array_merge([$group], $group['children'] ?? []) as $item) {
                if (isset($item['route'])) {
                    $names[] = $item['route'];
                }

                $names = array_merge($names, $item['also'] ?? []);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * An officer's own corner of the system: what they are, what they are
     * owed, and the few things they may ask for.
     */
    public static function selfService(): array
    {
        return [
            [
                // Their own landing page — the bio-data the Registry holds
                // on them, which is also where signing in sends them.
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'route' => 'my-bio-data',
            ],
            [
                // The same report the Registry works through, but scoped to
                // their own record there — see `appraisalStaff()`, which
                // reads it off the session and ignores whatever a `staff`
                // field in the url or the form happens to carry.
                'label' => 'Performance Appraisal',
                'icon' => 'chart',
                'route' => 'hr-appraisal',
                'also' => ['hr-appraisal.bio-data', 'hr-appraisal.career-history',
                           'hr-appraisal.qualifications', 'hr-appraisal.self-reporting',
                           'hr-appraisal.character-traits', 'hr-appraisal.commendations',
                           'hr-appraisal.sanctions', 'hr-appraisal.leadership-traits',
                           'hr-appraisal.general-remarks', 'hr-appraisal.suggestions',
                           'hr-appraisal.staff-declaration',
                           'hr-appraisal.reporting-officer-declaration',
                           'hr-appraisal.counter-signing-officer-report'],
            ],
            [
                'label' => 'Leave Management',
                'icon' => 'users',
                'route' => 'my-leave',
                'also' => ['my-leave.store', 'my-leave.show'],
            ],
            [
                'label' => 'Training',
                'icon' => 'certificate',
                'route' => 'my-training',
                'also' => ['my-training.interest'],
            ],
            [
                'label' => 'Voluntary Retirement',
                'icon' => 'archive',
                'route' => 'my-retirement',
                'also' => ['my-retirement.store'],
            ],
            [
                'label' => 'Accounts',
                'icon' => 'wallet',
                'children' => [
                    ['label' => 'Payslip', 'route' => 'my-payslip'],
                    ['label' => 'Deductions', 'route' => 'my-deductions'],
                ],
            ],
            [
                'label' => 'Profile',
                'icon' => 'user',
                'children' => [
                    // The Dashboard already is the bio-data, so this entry
                    // is the one thing the Profile group adds to it: asking
                    // for a correction.
                    ['label' => 'Request Update', 'route' => 'my-record-amendment',
                     'also' => ['my-record-amendment.store']],
                    ['label' => 'Change Password', 'route' => 'change-password',
                     'also' => ['change-password.store']],
                    // Update Photo is not here: it lives in the navbar's
                    // Options menu, which every role carries, so it is the
                    // one place it need not be repeated per menu.
                ],
            ],
        ];
    }

    /** The whole system, as the Administrator and the rest still see it. */
    public static function full(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Human Resources',
                'icon' => 'users',
                'children' => [
                    ['label' => 'Leave Management', 'route' => 'hr-leave',
                     'also' => ['hr-leave.store', 'hr-leave.show']],
                    ['label' => 'Promotion Exercises', 'route' => 'hr-promotions'],
                    ['label' => 'Performance Appraisal', 'route' => 'hr-appraisal',
                     'also' => ['hr-appraisal.bio-data', 'hr-appraisal.career-history',
                                'hr-appraisal.qualifications']],
                    ['label' => 'Recruitment', 'route' => 'hr-recruitment'],
                    ['label' => 'Training & Capacity', 'route' => 'hr-training'],
                    ['label' => 'Establishment Strength', 'route' => 'hr-establishment'],
                ],
            ],
            [
                'label' => 'Registry',
                'icon' => 'archive',
                'children' => [
                    // The wizard walks through its own pages; the entry that
                    // opened it stays highlighted the whole way.
                    ['label' => 'Add Staff Record', 'route' => 'basic-info',
                     'also' => ['edu-progress', 'promotion', 'leave-record', 'referee',
                                'next-of-kin', 'career', 'create']],
                    ['label' => 'Staff Bio-data', 'route' => 'bio-data'],
                    ['label' => 'Nominall Roll', 'route' => 'nominal-roll'],
                    ['label' => 'Posting & Deployment', 'route' => 'posting-deployment',
                     'also' => ['posting-deployment.store']],
                    ['label' => 'Confirmation of Appointment', 'route' => 'confirmation',
                     'also' => ['confirmation.store']],
                    ['label' => 'Record of Service', 'route' => 'record-of-service'],
                    ['label' => 'Promotion Brief', 'route' => 'promotion-brief',
                     'also' => ['promotion-brief.store', 'promotion-brief.entry',
                                'promotion-brief.review', 'promotion-brief.remove']],
                    ['label' => 'Query & Discipline', 'route' => 'query-discipline',
                     'also' => ['query-discipline.store', 'query-response', 'query-response.decide']],
                    ['label' => 'Record Amendments', 'route' => 'record-amendments',
                     'also' => ['record-amendments.store', 'record-amendments.review']],
                    ['label' => 'Family & Referees', 'route' => 'family-referees',
                     'also' => ['family-referees.next-of-kin.store', 'family-referees.next-of-kin.update',
                                'family-referees.next-of-kin.remove', 'family-referees.referees.store']],
                ],
            ],
            [
                'label' => 'Pension & Gratuity',
                'icon' => 'coins',
                'children' => [
                    ['label' => 'Retirement & Exit', 'route' => 'retirement-exit',
                     'also' => ['retirement-exit.store', 'benefit-payments.store',
                                'benefit-payments.receipt']],
                    ['label' => 'CPS', 'route' => 'cps'],
                    ['label' => 'Deceased', 'route' => 'deceased',
                     'also' => ['deceased.store', 'deceased.export']],
                    // An officer's own application to leave early. It sits
                    // here rather than in a corner of its own: leaving the
                    // service is what this section is about.
                    ['label' => 'Voluntary Retirement', 'route' => 'my-retirement',
                     'also' => ['my-retirement.store']],
                ],
            ],
            [
                'label' => 'Finance/Account',
                'icon' => 'wallet',
                'children' => [
                    ['label' => 'Salary Structure', 'route' => 'salary-structure'],
                    ['label' => 'Allowances', 'route' => 'allowances'],
                    ['label' => 'Staff Deductions', 'route' => 'staff-deductions'],
                    ['label' => 'Loans & Advances', 'route' => 'loans-advances'],
                    ['label' => 'Pay Roll', 'route' => 'payroll'],
                    ['label' => 'Pay Slip', 'route' => 'payslip'],
                    ['label' => 'Arrears & Variations', 'route' => 'arrears-variations'],
                    ['label' => 'Remittances', 'route' => 'remittances'],
                ],
            ],
            [
                'label' => 'ICT/Voter Registry',
                'icon' => 'fingerprint',
                'children' => [
                    ['label' => 'Overview', 'route' => 'voter-registry'],
                    ['label' => 'Ad-hoc Staff', 'route' => 'adhoc-staff'],
                    ['label' => 'Voter Registration', 'route' => 'voter-registration'],
                    ['label' => 'Polling Units', 'route' => 'polling-units'],
                    ['label' => 'Device & Asset Register', 'route' => 'device-register'],
                ],
            ],
            [
                'label' => 'Reports & Analytics',
                'icon' => 'chart',
                'children' => [
                    ['label' => 'Nominal Roll Report', 'route' => 'nominal-roll-report',
                     'also' => ['nominal-roll-report.export']],
                    ['label' => 'Retirement Projection', 'route' => 'retirement-projection',
                     'also' => ['retirement-projection.export']],
                    ['label' => 'Payroll Report', 'route' => 'payroll-report'],
                    ['label' => 'Headcount & Audit', 'route' => 'headcount-report',
                     'also' => ['headcount-report.export']],
                ],
            ],
            [
                'label' => 'Administration',
                'icon' => 'settings',
                'children' => [
                    ['label' => 'Settings', 'route' => 'config',
                     'also' => ['config.organisation', 'config.rules']],
                    ['label' => 'Departments & Units', 'route' => 'departments',
                     'also' => ['config.departments', 'config.department-heads',
                                'config.units', 'config.units.remove']],
                    ['label' => 'Users, Roles & Permissions', 'route' => 'users-roles',
                     'also' => ['users-roles.store', 'users-roles.toggle', 'users-roles.permissions']],
                    ['label' => 'Audit Trail', 'route' => 'audit-trail'],
                    ['label' => 'Reference Data', 'route' => 'reference-data'],
                    ['label' => 'Notification Templates', 'route' => 'notification-templates'],
                    ['label' => 'Backup & Data', 'route' => 'backup-data'],
                    // The one thing here that is about the account signed in
                    // rather than about the system.
                    ['label' => 'Change Password', 'route' => 'change-password',
                     'also' => ['change-password.store']],
                ],
            ],
        ];
    }
}
