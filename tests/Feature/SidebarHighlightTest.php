<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Which entry the sidebar shows as current.
 *
 * It used to be worked out from the url's segments. That could not tell the
 * self-service pages apart — they all sit under one prefix — so it is read off
 * the route being served instead. These pin the old behaviour down, so the
 * change does not quietly lose a highlight somewhere in the full menu.
 */
class SidebarHighlightTest extends TestCase
{
    use RefreshDatabase;

    /** The darker green an active sub-menu entry carries. */
    private const ACTIVE_CHILD = 'bg-[#1B5E20]/30';

    /** The content-coloured tab an open group carries. */
    private const ACTIVE_GROUP = 'bg-base-200 text-base-content rounded-l-lg';

    private function actor(string $role = 'Administrator'): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => $role,
        ]);
    }

    /** The label of whichever sub-menu entry is shown as current. */
    private function highlighted(string $route, string $role = 'Administrator'): ?string
    {
        $html = $this->actingAs($this->actor($role))->get(route($route))->getContent();

        $marker = preg_quote(self::ACTIVE_CHILD, '/');

        // The anchor carrying the active class, and the label inside it.
        return preg_match('/<a[^>]*' . $marker . '[^>]*>.*?<span>(.*?)<\/span>/s', $html, $m)
            ? html_entity_decode(trim($m[1]))
            : null;
    }

    public static function pagesAndTheirEntries(): array
    {
        return [
            'the nominal roll' => ['nominal-roll', 'Nominall Roll'],
            'staff bio-data' => ['bio-data', 'Staff Bio-data'],
            'posting and deployment' => ['posting-deployment', 'Posting & Deployment'],
            'confirmation' => ['confirmation', 'Confirmation of Appointment'],
            'record of service' => ['record-of-service', 'Record of Service'],
            'query and discipline' => ['query-discipline', 'Query & Discipline'],
            'record amendments' => ['record-amendments', 'Record Amendments'],
            'HR leave' => ['hr-leave', 'Leave Management'],
            'HR training' => ['hr-training', 'Training & Capacity'],
            'retirement and exit' => ['retirement-exit', 'Retirement & Exit'],
            'the deceased register' => ['deceased', 'Deceased'],
            'the payroll' => ['payroll', 'Pay Roll'],
            'the nominal roll report' => ['nominal-roll-report', 'Nominal Roll Report'],
            'headcount and audit' => ['headcount-report', 'Headcount & Audit'],
            'users and roles' => ['users-roles', 'Users, Roles & Permissions'],
            'backup and data' => ['backup-data', 'Backup & Data'],
            'settings' => ['config', 'Settings'],
            'the ICT overview' => ['voter-registry', 'Overview'],
        ];
    }

    #[DataProvider('pagesAndTheirEntries')]
    public function test_the_page_being_viewed_is_the_entry_shown_as_current(string $route, string $label): void
    {
        $this->assertSame($label, $this->highlighted($route));
    }

    public function test_the_wizards_first_step_is_add_staff_record(): void
    {
        $this->assertSame('Add Staff Record', $this->highlighted('basic-info'));
    }

    /**
     * The wizard walks through pages of its own, and the entry that opened it
     * stays current the whole way — that used to come of their sharing a url
     * segment, and is now spelled out.
     *
     * Asserted against the menu rather than the rendered page: the later steps
     * redirect to the first when there is no draft in the session, so there is
     * no sidebar on them to read.
     */
    public function test_the_wizards_later_steps_belong_to_add_staff_record(): void
    {
        $registry = collect(\App\Support\Menu::full())->firstWhere('label', 'Registry');
        $entry = collect($registry['children'])->firstWhere('label', 'Add Staff Record');

        foreach (['edu-progress', 'promotion', 'leave-record', 'referee',
                  'next-of-kin', 'career', 'create'] as $step) {
            $this->assertContains($step, $entry['also']);
        }
    }

    public function test_only_one_entry_is_current_at_a_time(): void
    {
        $html = $this->actingAs($this->actor())->get(route('nominal-roll'))->getContent();

        $this->assertSame(1, substr_count($html, self::ACTIVE_CHILD));
    }

    public function test_the_group_holding_the_current_page_is_open(): void
    {
        $html = $this->actingAs($this->actor())->get(route('nominal-roll'))->getContent();

        // Registry's summary carries the tab, and its <details> is open.
        $this->assertMatchesRegularExpression(
            '/<details[^>]*\sopen\s*>\s*<summary[^>]*' . preg_quote(self::ACTIVE_GROUP, '/') . '/',
            $html,
        );
    }

    public function test_the_dashboard_is_current_on_its_own_page(): void
    {
        $html = $this->actingAs($this->actor())->get(route('dashboard'))->getContent();

        // A group with no children carries the tab on the link itself.
        $this->assertMatchesRegularExpression(
            '/<a[^>]*' . preg_quote(self::ACTIVE_GROUP, '/') . '.*?<span>Dashboard<\/span>/s',
            $html,
        );
    }

    /* ------------------------------------------------ the self-service menu */

    public static function selfServicePagesAndTheirEntries(): array
    {
        return [
            'their payslip' => ['my-payslip', 'Payslip'],
            'their deductions' => ['my-deductions', 'Deductions'],
            'their bio-data' => ['my-bio-data', 'Bio-data'],
            'changing their password' => ['change-password', 'Change Password'],
            'their photo' => ['my-photo', 'Update Photo'],
            'voluntary retirement' => ['my-retirement', 'Voluntary Retirement'],
        ];
    }

    /**
     * The reason the highlight had to stop reading url segments: these all sit
     * under /dashboard/me, so segments would make every group current at once.
     */
    #[DataProvider('selfServicePagesAndTheirEntries')]
    public function test_an_officers_own_pages_are_told_apart(string $route, string $label): void
    {
        $this->assertSame($label, $this->highlighted($route, 'User'));
    }

    public function test_only_one_of_an_officers_entries_is_current(): void
    {
        $html = $this->actingAs($this->actor('User'))->get(route('my-payslip'))->getContent();

        $this->assertSame(1, substr_count($html, self::ACTIVE_CHILD));
    }
}
