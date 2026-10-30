<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * An officer leaves the service at 60 years of age or on 35 years of service,
 * whichever reaches them first. The mode is therefore worked out from the two
 * dates on the staff record, not chosen by hand.
 */
class ModeOfRetirementTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function officer(?string $born, ?string $employed): BasicInformation
    {
        return BasicInformation::factory()->create([
            'date_of_birth' => $born,
            'date_of_employment' => $employed,
        ])->fresh();
    }

    public function test_the_staff_record_carries_both_dates_and_the_mode(): void
    {
        $this->assertTrue(Schema::hasColumn('basic_informations', 'date_of_employment'));
        $this->assertTrue(Schema::hasColumn('basic_informations', 'mode_of_retirement'));
    }

    public function test_there_are_exactly_two_modes(): void
    {
        $this->assertSame([
            'years-of-service' => 'Years of Service',
            'age' => 'Retirement by Age',
        ], BasicInformation::RETIREMENT_MODES);
    }

    public function test_both_due_dates_are_worked_out_from_the_two_dates(): void
    {
        $due = $this->officer('1970-06-11', '1995-03-02')->retirementDueDates();

        $this->assertSame('2030-03-02', $due['years-of-service']->toDateString());
        $this->assertSame('2030-06-11', $due['age']->toDateString());
    }

    public function test_the_mode_is_years_of_service_when_that_comes_first(): void
    {
        // Appointed at 25, so 35 years of service lands at 60 less two years.
        $officer = $this->officer('1970-06-11', '1993-01-02');

        $this->assertSame('years-of-service', $officer->mode_of_retirement);
        $this->assertSame('2028-01-02', $officer->retiresOn()->toDateString());
    }

    public function test_the_mode_is_age_when_that_comes_first(): void
    {
        // Appointed late, so 60 arrives before 35 years of service.
        $officer = $this->officer('1970-06-11', '2005-03-02');

        $this->assertSame('age', $officer->mode_of_retirement);
        $this->assertSame('2030-06-11', $officer->retiresOn()->toDateString());
    }

    public function test_the_mode_is_worked_out_as_the_record_is_created(): void
    {
        // Never set by hand: whatever is passed in is overwritten.
        $officer = BasicInformation::factory()->create([
            'date_of_birth' => '1970-06-11',
            'date_of_employment' => '1993-01-02',
            'mode_of_retirement' => 'age',
        ]);

        $this->assertSame('years-of-service', $officer->fresh()->mode_of_retirement);
    }

    public function test_correcting_a_date_recalculates_the_mode(): void
    {
        $officer = $this->officer('1970-06-11', '1993-01-02');
        $this->assertSame('years-of-service', $officer->mode_of_retirement);

        // The appointment date was wrong; the mode must follow the correction.
        $officer->update(['date_of_employment' => '2005-03-02']);

        $this->assertSame('age', $officer->fresh()->mode_of_retirement);
    }

    public function test_an_officer_with_no_date_of_employment_falls_due_at_sixty(): void
    {
        $officer = $this->officer('1970-06-11', null);

        $this->assertSame('age', $officer->mode_of_retirement);
        $this->assertSame('2030-06-11', $officer->retiresOn()->toDateString());
    }

    public function test_an_officer_with_neither_date_has_no_mode(): void
    {
        // Not persisted: date_of_birth is NOT NULL on the table, so this is
        // only reachable in memory.
        $officer = new BasicInformation(['date_of_birth' => null, 'date_of_employment' => null]);

        $this->assertNull($officer->determineRetirementMode());
        $this->assertNull($officer->retiresOn());
    }

    public function test_the_wizard_captures_the_date_of_employment(): void
    {
        $state = \App\Models\State::where('name', 'Cross River')->firstOrFail();

        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'file-number' => 'MODE/F/0001',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => '',
            'department' => 'Department of Electoral Operations', 'division' => 'Calabar HQ',
            'gender' => 'Male',
            'designation' => 'Executive Officer (EO)', 'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1970-06-11',
            'date-of-employment' => '1993-01-02',
            'phone-number' => '08030000001', 'email' => 'bassey@inec.gov.ng',
            'state-of-origin' => $state->id, 'lga-of-origin' => $state->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ])->assertRedirect(route('edu-progress'));

        $officer = BasicInformation::where('file_number', 'MODE/F/0001')->firstOrFail();

        $this->assertSame('1993-01-02', $officer->date_of_employment);
        // Worked out on the way in, without anyone choosing it.
        $this->assertSame('years-of-service', $officer->mode_of_retirement);
    }

    public function test_the_date_of_employment_is_required(): void
    {
        $state = \App\Models\State::where('name', 'Cross River')->firstOrFail();

        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'file-number' => 'MODE/F/0002',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => '',
            'department' => 'Department of Electoral Operations', 'division' => 'Calabar HQ',
            'gender' => 'Male',
            'designation' => 'Executive Officer (EO)', 'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1970-06-11',
            'phone-number' => '08030000001', 'email' => 'bassey@inec.gov.ng',
            'state-of-origin' => $state->id, 'lga-of-origin' => $state->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ])->assertSessionHasErrors('date-of-employment');
    }

    public function test_an_officer_cannot_be_employed_before_they_were_born(): void
    {
        $state = \App\Models\State::where('name', 'Cross River')->firstOrFail();

        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'file-number' => 'MODE/F/0003',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => '',
            'department' => 'Department of Electoral Operations', 'division' => 'Calabar HQ',
            'gender' => 'Male',
            'designation' => 'Executive Officer (EO)', 'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1970-06-11',
            'date-of-employment' => '1960-01-02',
            'phone-number' => '08030000001', 'email' => 'bassey@inec.gov.ng',
            'state-of-origin' => $state->id, 'lga-of-origin' => $state->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ])->assertSessionHasErrors('date-of-employment');
    }

    public function test_recording_an_exit_does_not_ask_for_a_mode(): void
    {
        $officer = $this->officer('1970-06-11', '1993-01-02');

        // The form carries no mode field, and the exit saves without one.
        $this->actingAs($this->actor())
            ->get(route('retirement-exit'))
            ->assertDontSee('name="mode_of_retirement"', false);

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'retirement',
            'effective_date' => '2028-01-02',
            'gratuity_status' => 'pending',
            'pension_status' => 'pending',
        ])->assertRedirect(route('retirement-exit'));

        $this->assertSame(1, StaffExit::count());
        // Still what the dates say, untouched by the exit.
        $this->assertSame('years-of-service', $officer->fresh()->mode_of_retirement);
    }

    public function test_the_due_to_retire_table_shows_the_mode_and_date_of_employment(): void
    {
        // Falls due inside the five-year window the table covers.
        $this->officer(now()->subYears(57)->toDateString(), '1995-03-02');

        $response = $this->actingAs($this->actor())->get(route('retirement-exit'));

        $response->assertOk();
        $response->assertSee('Mode of Retirement');
        $response->assertSee('Date of Employment');
        $response->assertSee('1995-03-02');
    }

    public function test_the_projection_uses_whichever_route_comes_first(): void
    {
        $officer = $this->officer(now()->subYears(50)->toDateString(), now()->subYears(33)->toDateString());

        $row = collect($this->actingAs($this->actor())->get(route('retirement-exit'))->viewData('projection'))
            ->firstWhere(fn ($r) => $r->staff->ulid === $officer->ulid);

        $this->assertNotNull($row, 'an officer two years from 35 years of service should be listed');
        $this->assertSame('Years of Service', $row->mode);
        $this->assertSame($officer->retiresOn()->toDateString(), $row->retires_on->toDateString());
    }
}
