<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\PromotionBrief;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HOD GAP is a staff member first: their own appraisal is theirs to fill, and
 * on every other officer's they read all of it and act only on the
 * Suggestions (By Superior Officer), by signing and saying the day.
 */
class HodGapAppraisalTest extends TestCase
{
    use RefreshDatabase;

    private function hodGap(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'HOD GAP',
        ]);
    }

    private function onBrief(BasicInformation $staff): BasicInformation
    {
        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);

        return $staff;
    }

    private function officer(): BasicInformation
    {
        return $this->onBrief(BasicInformation::factory()->create([
            'department' => 'Department of Electoral Operations',
        ]));
    }

    private function section(User $viewer, BasicInformation $staff, string $section)
    {
        return $this->actingAs($viewer)->get(route('hr-appraisal', [
            'section' => $section, 'staff' => $staff->ulid,
        ]));
    }

    private function suggest(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.suggestions'), array_merge([
            'staff' => $staff->ulid,
            'section' => 'suggestions',
            'different_job_suggested' => 'no',
            'acted_on' => '2026-11-23',
            'signature' => 'HOD GAP',
        ], $overrides));
    }

    /* ------------------------------------------ the staff view of the page */

    public function test_a_staff_account_sees_appraisal_for_you_above_the_sections_and_no_name_anywhere(): void
    {
        $staff = $this->officer();
        $self = User::factory()->create(['basic_information_ulid' => $staff->ulid, 'role' => 'User']);

        $html = $this->section($self, $staff, 'bio-data')->assertOk()->getContent();

        $this->assertStringContainsString('Appraisal for', $html);
        $this->assertMatchesRegularExpression('/Appraisal for\s*<span[^>]*>you<\/span>/', $html);
        $this->assertStringNotContainsString('Your Report', $html);
        // The card heading is the section title alone.
        $this->assertStringNotContainsString('For ' . $staff->surname, $html);
        $this->assertStringNotContainsString("({$staff->staff_number})", $html);
    }

    public function test_other_roles_still_see_the_officers_name_and_number(): void
    {
        $staff = $this->officer();

        $this->section($this->hodGap(), $staff, 'bio-data')
            ->assertSee("{$staff->surname}, {$staff->firstname}")
            ->assertSee("({$staff->staff_number})");
    }

    /* ----------------------------------------------------------- the sidebar */

    public function test_the_sidebar_carries_performance_appraisal_in_its_own_right(): void
    {
        $labels = array_column(Menu::forUser($this->hodGap()), 'label');

        $this->assertSame('Performance Appraisal', $labels[1]);
    }

    public function test_it_is_not_listed_twice(): void
    {
        $routes = [];

        foreach (Menu::forUser($this->hodGap()) as $group) {
            foreach (array_merge([$group], $group['children'] ?? []) as $item) {
                $routes[] = $item['route'] ?? null;
            }
        }

        $this->assertSame(1, count(array_filter($routes, fn ($r) => $r === 'hr-appraisal')));
    }

    public function test_finance_is_cut_down_to_what_hod_gap_deals_with(): void
    {
        $finance = collect(Menu::forUser($this->hodGap()))->firstWhere('label', 'Finance/Account');

        $this->assertSame(
            ['Pay Slip Request', 'Loans & Advances', 'Deductions', 'Allowances'],
            array_column($finance['children'], 'label'),
        );
    }

    public function test_the_ict_voter_registry_is_not_on_their_sidebar(): void
    {
        $this->assertNotContains('ICT/Voter Registry', array_column(Menu::forUser($this->hodGap()), 'label'));
    }

    public function test_hod_gap_is_refused_the_pages_they_have_no_part_in(): void
    {
        $hodGap = $this->hodGap();

        foreach (['salary-structure', 'payroll', 'arrears-variations', 'remittances',
                  'voter-registry', 'adhoc-staff', 'voter-registration', 'polling-units',
                  'device-register'] as $route) {
            $this->actingAs($hodGap)->get(route($route))->assertForbidden();
        }
    }

    public function test_hod_gap_reaches_the_finance_pages_on_their_menu(): void
    {
        $hodGap = $this->hodGap();

        foreach (['payslip', 'loans-advances', 'staff-deductions', 'allowances'] as $route) {
            $this->actingAs($hodGap)->get(route($route))->assertOk();
        }
    }

    public function test_the_denied_pages_are_not_followed_after_signing_in(): void
    {
        $this->assertFalse(Menu::allows($this->hodGap(), route('voter-registry')));
        $this->assertTrue(Menu::allows($this->hodGap(), route('allowances')));
    }

    public function test_other_roles_keep_the_menu_they_had(): void
    {
        $admin = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);

        $this->assertNotContains('Performance Appraisal', array_column(Menu::forUser($admin), 'label'));
    }

    /* --------------------------------------------- acting on the Suggestions */

    public function test_hod_gap_acts_on_any_officers_suggestions(): void
    {
        $staff = $this->officer();

        $this->section($this->hodGap(), $staff, 'suggestions')
            ->assertSee('name="different_job_suggested"', false)
            ->assertSee('name="acted_on"', false)
            ->assertSee('name="signature"', false);

        $this->suggest($this->hodGap(), $staff, [
            'different_job_suggested' => 'yes', 'different_job_details' => 'Field operations.',
        ])->assertRedirect();

        $suggestion = Suggestion::sole();

        $this->assertTrue($suggestion->different_job_suggested);
        $this->assertSame('2026-11-23', $suggestion->acted_on->toDateString());
        $this->assertSame('HOD GAP', $suggestion->signature);
    }

    public function test_the_date_and_signature_are_required(): void
    {
        $staff = $this->officer();
        $hodGap = $this->hodGap();

        $this->suggest($hodGap, $staff, ['acted_on' => ''])->assertSessionHasErrors('acted_on');
        $this->suggest($hodGap, $staff, ['signature' => ''])->assertSessionHasErrors('signature');
    }

    public function test_the_date_and_signature_show_to_those_who_may_not_act(): void
    {
        $staff = $this->officer();

        Suggestion::create([
            'basic_information_ulid' => $staff->ulid,
            'different_job_suggested' => false,
            'acted_on' => '2026-11-20', 'signature' => 'A. Signer',
        ]);

        $other = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'HOD ICT',
        ]);

        $this->section($other, $staff, 'suggestions')
            ->assertSee('A. Signer')
            ->assertSee('20 Nov 2026');
    }

    /* ------------------------------------------ everything else is read-only */

    public function test_hod_gap_reads_other_officers_reports_but_may_not_write_them(): void
    {
        $staff = $this->officer();
        $hodGap = $this->hodGap();

        $this->section($hodGap, $staff, 'bio-data')
            ->assertOk()
            ->assertSee('You are reading another officer');

        $this->actingAs($hodGap)->post(route('hr-appraisal.general-remarks'), [
            'staff' => $staff->ulid, 'section' => 'general-remarks', 'remarks' => 'Anything.',
        ])->assertForbidden();

        $this->actingAs($hodGap)->post(route('hr-appraisal.commendations'), [
            'staff' => $staff->ulid, 'section' => 'commendations',
        ])->assertForbidden();
    }

    public function test_the_suggestions_section_is_not_locked_for_hod_gap(): void
    {
        $this->section($this->hodGap(), $this->officer(), 'suggestions')
            ->assertDontSee('<fieldset class="m-0 min-w-0 border-0 p-0" disabled', false);
    }

    public function test_hod_gap_fills_their_own_report_as_a_staff_member(): void
    {
        $hodGap = $this->hodGap();
        $this->onBrief($hodGap->staff);

        $this->section($hodGap, $hodGap->staff, 'bio-data')
            ->assertOk()
            ->assertDontSee('You are reading another officer');

        $this->actingAs($hodGap)->post(route('hr-appraisal.staff-declaration'), [
            'staff' => $hodGap->staff->ulid, 'section' => 'staff-declaration',
            'comments' => 'No comments.', 'declared_on' => '2026-11-23', 'signature' => 'HOD GAP',
        ])->assertRedirect();
    }
}
