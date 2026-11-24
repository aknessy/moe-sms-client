<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\CounterSigningReport;
use App\Models\PromotionBrief;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Countersigning Officer's Report: the reporting officer's superior's
 * agreement or comment, time served under them, and who they are.
 */
class AppraisalCounterSigningReportTest extends TestCase
{
    use RefreshDatabase;

    private function officer(): BasicInformation
    {
        $staff = BasicInformation::factory()->create([
            'department' => 'Department of Electoral Operations',
        ]);

        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);

        return $staff;
    }

    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    private function sectionAs(User $viewer, BasicInformation $staff)
    {
        return $this->actingAs($viewer)->get(route('hr-appraisal', [
            'section' => 'counter-signing-officer-report', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.counter-signing-officer-report'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'counter-signing-officer-report',
                'comments' => 'I agree with the reporting officer. I see this officer weekly.',
                'served_years' => 3,
                'served_months' => 4,
                'post_held' => 'Administrative Secretary',
                'grade_level' => '17',
                'officer_name' => 'Ima Okon',
                'declared_on' => '2026-11-21',
                'signature' => 'I. Okon',
            ], $overrides));
    }

    public function test_the_section_shows_the_form(): void
    {
        $staff = $this->officer();

        $this->sectionAs($this->account('Administrator'), $staff)
            ->assertOk()
            ->assertSee('immediate superior of the reporting officer')
            ->assertSee('name="comments"', false)
            ->assertSee('name="served_years"', false)
            ->assertSee('name="served_months"', false)
            ->assertSee('Name of Countersigning Officer')
            ->assertSee('name="signature"', false);
    }

    public function test_the_report_reaches_the_record_and_stays_on_the_last_section(): void
    {
        $staff = $this->officer();

        $this->saveAs($this->account('Administrative Secretary'), $staff)
            ->assertRedirect(route('hr-appraisal', [
                'section' => 'counter-signing-officer-report', 'staff' => $staff->ulid,
            ]));

        $report = CounterSigningReport::sole();

        $this->assertSame(3, $report->served_years);
        $this->assertSame(4, $report->served_months);
        $this->assertSame('Ima Okon', $report->officer_name);
        $this->assertSame('2026-11-21', $report->declared_on->toDateString());
    }

    public function test_saving_again_corrects_the_one_report(): void
    {
        $staff = $this->officer();
        $admin = $this->account('Administrator');

        $this->saveAs($admin, $staff);
        $this->saveAs($admin, $staff, ['served_years' => 5]);

        $this->assertSame(5, CounterSigningReport::sole()->served_years);
    }

    public function test_every_field_is_required(): void
    {
        $staff = $this->officer();

        foreach (['comments', 'served_years', 'served_months', 'post_held',
                  'grade_level', 'officer_name', 'declared_on', 'signature'] as $field) {
            $this->saveAs($this->account('Administrator'), $staff, [$field => ''])
                ->assertSessionHasErrors($field);
        }
    }

    public function test_months_cannot_be_a_full_year(): void
    {
        $staff = $this->officer();

        $this->saveAs($this->account('Administrator'), $staff, ['served_months' => 12])
            ->assertSessionHasErrors('served_months');
    }

    public function test_the_desks_above_the_reporting_officers_write_it(): void
    {
        $staff = $this->officer();

        foreach (['Administrator', 'Administrative Secretary', 'Commissioner'] as $role) {
            $this->saveAs($this->account($role), $staff)->assertRedirect();
        }
    }

    public function test_a_head_of_department_only_reads_on(): void
    {
        $staff = $this->officer();
        $head = $this->account('HOD EOPs');

        $this->sectionAs($head, $staff)->assertDontSee('name="signature"', false);
        $this->saveAs($head, $staff)->assertForbidden();
        $this->assertSame(0, CounterSigningReport::count());
    }

    public function test_the_officer_themselves_may_not_write_it(): void
    {
        $staff = $this->officer();
        $self = User::factory()->create(['basic_information_ulid' => $staff->ulid, 'role' => 'User']);

        $this->saveAs($self, $staff)->assertForbidden();
    }

    public function test_a_report_on_record_shows_to_those_who_may_not_write_it(): void
    {
        $staff = $this->officer();

        CounterSigningReport::create([
            'basic_information_ulid' => $staff->ulid,
            'comments' => 'Fully agreed.', 'served_years' => 2, 'served_months' => 6,
            'post_held' => 'Commissioner', 'grade_level' => '17',
            'officer_name' => 'Ima Okon', 'declared_on' => '2026-11-21', 'signature' => 'I. Okon',
        ]);

        $this->sectionAs($this->account('HOD ICT'), $staff)
            ->assertSee('Fully agreed.')
            ->assertSee('2 years and 6 months')
            ->assertSee('Ima Okon');
    }
}
