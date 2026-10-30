<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The sidebar follows the role of whoever is signed in, and what it points at
 * is also what they are allowed to reach — hiding a page that still answers to
 * its url would be no restriction at all.
 */
class SelfServiceMenuTest extends TestCase
{
    use RefreshDatabase;

    private function officer(string $role, array $attributes = []): User
    {
        $staff = BasicInformation::factory()->create($attributes);

        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
    }

    /* --------------------------------------------- which menu is shown */

    public function test_an_officer_on_the_user_role_sees_only_their_own_corner(): void
    {
        $response = $this->actingAs($this->officer('User'))->get(route('my-appraisal'));

        foreach (['Performance Appraisal', 'Leave Management', 'Training',
                  'Accounts', 'Profile'] as $entry) {
            $response->assertSee($entry);
        }

        // Nothing administrative reaches them. These are entries nothing else
        // on the page says — the word "Registry" on its own appears in the
        // copy, telling them where their record is held. assertSee escapes
        // what it is given, so they are written as labels, not as rendered.
        foreach (['Nominall Roll', 'Posting & Deployment', 'Record of Service',
                  'Pension & Gratuity', 'ICT/Voter Registry', 'Reports & Analytics',
                  'Users, Roles & Permissions', 'Backup & Data', 'Audit Trail',
                  'Establishment Strength', 'Salary Structure'] as $entry) {
            $response->assertDontSee($entry);
        }
    }

    public function test_the_administrator_still_sees_the_whole_menu(): void
    {
        $response = $this->actingAs($this->officer('Administrator'))->get(route('dashboard'));

        foreach (['Dashboard', 'Human Resources', 'Registry', 'Pension & Gratuity',
                  'Finance/Account', 'ICT/Voter Registry', 'Reports & Analytics',
                  'Administration'] as $entry) {
            $response->assertSee($entry);
        }
    }

    public static function rolesWithTheRunOfTheSystem(): array
    {
        return [
            ['Commissioner'], ['Administrative Secretary'], ['Registry Officer'],
            ['HOD GAP'], ['HOD ICT'], ['HOD EOPs'], ['HOD EPM'], ['HOD VEP'],
        ];
    }

    /** Only `User` is narrowed for now; the rest keep what they had. */
    #[DataProvider('rolesWithTheRunOfTheSystem')]
    public function test_every_other_role_keeps_the_full_menu(string $role): void
    {
        $this->actingAs($this->officer($role))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Registry');
    }

    /* --------------------------------------------------- the order shown */

    public function test_the_self_service_menu_runs_in_the_order_asked_for(): void
    {
        $this->assertSame(
            ['Performance Appraisal', 'Leave Management', 'Training', 'Accounts', 'Profile'],
            array_column(Menu::selfService(), 'label'),
        );
    }

    public function test_accounts_carries_the_payslip_and_deductions(): void
    {
        $accounts = collect(Menu::selfService())->firstWhere('label', 'Accounts');

        $this->assertSame(['Payslip', 'Deductions'], array_column($accounts['children'], 'label'));
    }

    public function test_profile_carries_the_four_things_an_officer_may_change(): void
    {
        $profile = collect(Menu::selfService())->firstWhere('label', 'Profile');

        $this->assertSame(
            ['Bio-data', 'Change Password', 'Update Photo', 'Voluntary Retirement'],
            array_column($profile['children'], 'label'),
        );
    }

    /* ------------------------------------------------------- enforcement */

    public static function pagesClosedToAnOfficer(): array
    {
        return [
            'the nominal roll' => ['nominal-roll'],
            'staff bio-data' => ['bio-data'],
            'the staff wizard' => ['basic-info'],
            'posting and deployment' => ['posting-deployment'],
            'record of service' => ['record-of-service'],
            'retirement and exit' => ['retirement-exit'],
            'the deceased register' => ['deceased'],
            'the payroll' => ['payroll'],
            'the salary structure' => ['salary-structure'],
            'the nominal roll report' => ['nominal-roll-report'],
            'headcount and audit' => ['headcount-report'],
            'users and roles' => ['users-roles'],
            'backup and data' => ['backup-data'],
            'the audit trail' => ['audit-trail'],
            'HR leave management' => ['hr-leave'],
            'voter registry' => ['voter-registry'],
        ];
    }

    /** Hiding the entry is not the point; the url has to be refused too. */
    #[DataProvider('pagesClosedToAnOfficer')]
    public function test_an_officer_is_refused_a_page_outside_their_menu(string $route): void
    {
        $this->actingAs($this->officer('User'))->get(route($route))->assertForbidden();
    }

    public function test_an_officer_may_not_post_to_a_page_outside_their_menu(): void
    {
        // The listing being hidden would not stop a form being submitted to it.
        $this->actingAs($this->officer('User'))
            ->post(route('hr-leave.store'), [])
            ->assertForbidden();
    }

    public static function pagesOpenToAnOfficer(): array
    {
        return [
            'their appraisal' => ['my-appraisal'],
            'their leave' => ['my-leave'],
            'training' => ['my-training'],
            'their payslip' => ['my-payslip'],
            'their deductions' => ['my-deductions'],
            'their bio-data' => ['my-bio-data'],
            'their photo' => ['my-photo'],
            'voluntary retirement' => ['my-retirement'],
            'changing their password' => ['change-password'],
        ];
    }

    #[DataProvider('pagesOpenToAnOfficer')]
    public function test_an_officer_may_reach_their_own_pages(string $route): void
    {
        $this->actingAs($this->officer('User'))->get(route($route))->assertOk();
    }

    public function test_an_administrator_is_refused_nothing(): void
    {
        $this->actingAs($this->officer('Administrator'))->get(route('nominal-roll'))->assertOk();
    }

    /* ----------------------------------------------------- landing page */

    public function test_the_dashboard_sends_an_officer_to_their_own_page(): void
    {
        // Not a refusal: the dashboard is where signing in lands them, and
        // they never asked for it.
        $this->actingAs($this->officer('User'))->get(route('dashboard'))
            ->assertRedirect(route('my-appraisal'));
    }

    public function test_signing_in_lands_an_officer_on_their_own_page(): void
    {
        $officer = $this->officer('User');

        $this->post('/login', [
            'userhandle' => $officer->username,
            'secrethash' => 'password',
        ])->assertRedirect(route('my-appraisal'));
    }

    public function test_signing_in_does_not_follow_an_officer_to_a_page_they_may_not_see(): void
    {
        $officer = $this->officer('User');

        // Bounced to the login from an administrative page: following them
        // back to it would sign them straight into a 403.
        $this->get(route('nominal-roll'))->assertRedirect(route('login'));

        $this->post('/login', [
            'userhandle' => $officer->username,
            'secrethash' => 'password',
        ])->assertRedirect(route('my-appraisal'));
    }

    public function test_signing_in_does_follow_an_officer_to_a_page_that_is_theirs(): void
    {
        $officer = $this->officer('User');

        $this->get(route('my-training'))->assertRedirect(route('login'));

        $this->post('/login', [
            'userhandle' => $officer->username,
            'secrethash' => 'password',
        ])->assertRedirect(route('my-training'));
    }

    public function test_an_administrator_is_still_followed_to_where_they_were_going(): void
    {
        $admin = $this->officer('Administrator');

        $this->get(route('nominal-roll'))->assertRedirect(route('login'));

        $this->post('/login', [
            'userhandle' => $admin->username,
            'secrethash' => 'password',
        ])->assertRedirect(route('nominal-roll'));
    }

    public function test_an_account_with_no_staff_record_is_turned_away(): void
    {
        $orphan = User::factory()->create(['role' => 'User']);
        $orphan->forceFill(['basic_information_ulid' => null])->save();

        $this->actingAs($orphan)->get(route('my-appraisal'))->assertForbidden();
    }

    /* ------------------------------------------------- the menu as data */

    public function test_the_allow_list_is_read_off_the_menu(): void
    {
        $allowed = Menu::allowedRoutesFor($this->officer('User'));

        // Everything the menu points at, and the POSTs that belong to it.
        foreach (['my-appraisal', 'my-leave', 'my-leave.store', 'my-training',
                  'my-training.interest', 'change-password.store'] as $route) {
            $this->assertContains($route, $allowed);
        }

        $this->assertNotContains('nominal-roll', $allowed);
    }

    public function test_an_unrestricted_role_has_no_allow_list_at_all(): void
    {
        // Null rather than a list of everything: a page added to the system
        // should never need permitting twice.
        $this->assertNull(Menu::allowedRoutesFor($this->officer('Administrator')));
    }

    public function test_every_route_the_menu_names_actually_exists(): void
    {
        $routes = array_merge(
            Menu::routeNames(Menu::full()),
            Menu::routeNames(Menu::selfService()),
            Menu::ALWAYS_ALLOWED,
        );

        foreach ($routes as $name) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($name),
                "The menu names `{$name}`, which is not a route.");
        }
    }
}
