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
    ];

    /** Where a self-service officer lands, and is sent back to. */
    public const SELF_SERVICE_HOME = 'my-appraisal';

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

        if ($allowed === null) {
            return true;
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
        } catch (\Throwable) {
            // Nothing answers to it, so there is nothing to follow.
            return false;
        }

        return in_array($route->getName(), $allowed, true);
    }

    /** The menu this account should see. */
    public static function forUser(?User $user): array
    {
        return self::isSelfService($user) ? self::selfService() : self::full();
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
                // Their landing page, and the nearest thing they have to a
                // dashboard: standing, posting and retirement all on it.
                'label' => 'Performance Appraisal',
                'icon' => 'chart',
                'route' => 'my-appraisal',
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
                    ['label' => 'Bio-data', 'route' => 'my-bio-data'],
                    ['label' => 'Change Password', 'route' => 'change-password',
                     'also' => ['change-password.store']],
                    ['label' => 'Update Photo', 'route' => 'my-photo',
                     'also' => ['my-photo.store', 'my-photo.show']],
                    ['label' => 'Voluntary Retirement', 'route' => 'my-retirement',
                     'also' => ['my-retirement.store']],
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
                    ['label' => 'Performance Appraisal', 'route' => 'hr-appraisal'],
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
                    ['label' => 'Query & Discipline', 'route' => 'query-discipline',
                     'also' => ['query-discipline.store', 'query-response', 'query-response.decide']],
                    ['label' => 'Record Amendments', 'route' => 'record-amendments',
                     'also' => ['record-amendments.store', 'record-amendments.review']],
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
                     'also' => ['config.organisation', 'config.departments',
                                'config.department-heads']],
                    ['label' => 'Users, Roles & Permissions', 'route' => 'users-roles',
                     'also' => ['users-roles.store', 'users-roles.toggle', 'users-roles.permissions']],
                    ['label' => 'Audit Trail', 'route' => 'audit-trail'],
                    ['label' => 'Reference Data', 'route' => 'reference-data'],
                    ['label' => 'Notification Templates', 'route' => 'notification-templates'],
                    ['label' => 'Backup & Data', 'route' => 'backup-data'],
                ],
            ],
        ];
    }
}
