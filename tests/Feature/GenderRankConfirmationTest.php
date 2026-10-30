<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Confirmation;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gender and the date an appointment was confirmed now sit on the staff
 * record. Rank and designation are the same thing, chosen from a fixed list;
 * the nominal roll reads the column as rank.
 */
class GenderRankConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function payload(array $overrides = []): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return array_merge([
            'form-type' => 'basic-information',
            'file-number' => 'GRC/F/0001',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => 'Effiom',
            'gender' => 'Male',
            'department' => 'Operations', 'division' => 'Calabar HQ',
            'designation' => 'Administrative Officer I (AO I)',
            'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04',
            'date-of-confirmation' => '2012-01-04',
            'phone-number' => '08030000001', 'email' => 'bassey.eyo@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ], $overrides);
    }

    private function submit(array $overrides = [])
    {
        return $this->actingAs($this->actor())
            ->post(route('create'), $this->payload($overrides));
    }

    private function recorded(): ?BasicInformation
    {
        return BasicInformation::where('file_number', 'GRC/F/0001')->first();
    }

    /* ------------------------------------------------------------ gender */

    public function test_gender_reaches_the_record(): void
    {
        $this->submit()->assertSessionHasNoErrors();

        $this->assertSame('Male', $this->recorded()->gender);
    }

    public function test_the_two_genders_are_offered(): void
    {
        $this->assertSame(['Male', 'Female'], BasicInformation::GENDERS);
    }

    public function test_a_gender_outside_the_two_is_refused(): void
    {
        $this->submit(['gender' => 'Other'])->assertSessionHasErrors('gender');

        $this->assertNull($this->recorded());
    }

    public function test_the_form_offers_both_genders(): void
    {
        $response = $this->actingAs($this->actor())->get(route('basic-info'));

        $response->assertSee('name="gender"', false);
        $response->assertSee('>Male<', false);
        $response->assertSee('>Female<', false);
    }

    /* -------------------------------------------------------------- rank */

    public function test_the_rank_chosen_reaches_the_record(): void
    {
        $this->submit();

        $this->assertSame('Administrative Officer I (AO I)', $this->recorded()->designation);
    }

    public function test_rank_reads_the_designation_column(): void
    {
        $this->submit();

        // Two names for the same thing.
        $staff = $this->recorded();
        $this->assertSame($staff->designation, $staff->rank);
    }

    public function test_a_rank_outside_the_list_is_refused(): void
    {
        // Free text used to be accepted here.
        $this->submit(['designation' => 'Chief Wizard'])->assertSessionHasErrors('designation');

        $this->assertNull($this->recorded());
    }

    public function test_every_rank_in_the_list_is_accepted(): void
    {
        foreach (BasicInformation::RANKS as $index => $rank) {
            // Each is a fresh record: the wizard will not reopen a draft's
            // personal information once it has been captured.
            $this->flushSession();

            $this->submit([
                'designation' => $rank,
                'file-number' => 'GRC/F/' . $index,
                'email' => "officer{$index}@inec.gov.ng",
            ])->assertSessionHasNoErrors();

            $this->assertSame($rank, BasicInformation::where('file_number', 'GRC/F/' . $index)->value('designation'));
        }
    }

    public function test_the_list_runs_from_director_down_to_security_guard(): void
    {
        $this->assertSame('Director', BasicInformation::RANKS[0]);
        $this->assertSame('Security Guard', BasicInformation::RANKS[array_key_last(BasicInformation::RANKS)]);
        $this->assertCount(13, BasicInformation::RANKS);
    }

    public function test_the_form_offers_every_rank(): void
    {
        $response = $this->actingAs($this->actor())->get(route('basic-info'));

        foreach (BasicInformation::RANKS as $rank) {
            $response->assertSee('value="' . e($rank) . '"', false);
        }
    }

    /* ---------------------------------------------- date of confirmation */

    public function test_the_date_of_confirmation_reaches_the_record(): void
    {
        $this->submit();

        $this->assertSame('2012-01-04', $this->recorded()->date_of_confirmation);
    }

    public function test_an_officer_still_on_probation_has_none(): void
    {
        $this->submit(['date-of-confirmation' => ''])->assertSessionHasNoErrors();

        $this->assertNull($this->recorded()->date_of_confirmation);
    }

    public function test_an_appointment_cannot_be_confirmed_before_it_began(): void
    {
        $this->submit(['date-of-confirmation' => '2009-01-04'])
            ->assertSessionHasErrors('date-of-confirmation');

        $this->assertNull($this->recorded());
    }

    public function test_an_appointment_cannot_be_confirmed_in_the_future(): void
    {
        $this->submit(['date-of-confirmation' => now()->addYear()->toDateString()])
            ->assertSessionHasErrors('date-of-confirmation');
    }

    /* ------------------------------ the registry exercise keeps it in step */

    public function test_confirming_an_appointment_writes_the_date_onto_the_record(): void
    {
        $staff = BasicInformation::factory()->create(['date_of_confirmation' => null]);

        $this->actingAs($this->actor())->post(route('confirmation.store'), [
            'basic_information_ulid' => $staff->ulid,
            'appointment_date' => '2020-01-06',
            'confirmation_date' => '2022-01-06',
            'status' => 'confirmed',
        ])->assertRedirect(route('confirmation'));

        $this->assertSame('2022-01-06', $staff->fresh()->date_of_confirmation);
    }

    public function test_an_exercise_still_pending_leaves_the_record_alone(): void
    {
        $staff = BasicInformation::factory()->create(['date_of_confirmation' => null]);

        $this->actingAs($this->actor())->post(route('confirmation.store'), [
            'basic_information_ulid' => $staff->ulid,
            'appointment_date' => '2020-01-06',
            'status' => 'pending',
        ]);

        $this->assertNull($staff->fresh()->date_of_confirmation);
        $this->assertSame(1, Confirmation::where('basic_information_ulid', $staff->ulid)->count());
    }

    public function test_a_deferred_exercise_leaves_the_record_alone(): void
    {
        $staff = BasicInformation::factory()->create(['date_of_confirmation' => '2015-05-05']);

        $this->actingAs($this->actor())->post(route('confirmation.store'), [
            'basic_information_ulid' => $staff->ulid,
            'appointment_date' => '2020-01-06',
            'confirmation_date' => '2022-01-06',
            'status' => 'deferred',
        ]);

        $this->assertSame('2015-05-05', $staff->fresh()->date_of_confirmation);
    }

    /* ------------------------------------------------ the nominal roll */

    public function test_the_nominal_roll_calls_it_rank(): void
    {
        $response = $this->actingAs($this->actor())->get(route('nominal-roll'));

        $response->assertOk();
        $response->assertSee('Rank');
        $response->assertDontSee('Designation');
    }

    public function test_the_nominal_roll_shows_gender(): void
    {
        BasicInformation::factory()->create(['gender' => 'Female', 'surname' => 'Okon']);

        $this->actingAs($this->actor())->get(route('nominal-roll'))->assertSee('Gender');
    }

    public function test_the_nominal_roll_report_calls_it_rank_too(): void
    {
        $response = $this->actingAs($this->actor())->get(route('nominal-roll-report'));

        $response->assertOk();
        $response->assertSee('Rank');
        $response->assertDontSee('Designation');
    }
}
