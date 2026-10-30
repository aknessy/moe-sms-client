<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\State;
use App\Models\Training;
use App\Models\TrainingInterest;
use App\Models\User;
use App\Models\VoluntaryRetirementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * An officer's own pages. Each is about whoever is signed in and nobody else,
 * so what they show, and what they write, comes off the session rather than
 * off the request.
 */
class SelfServicePagesTest extends TestCase
{
    use RefreshDatabase;

    private BasicInformation $staff;

    private function officer(array $attributes = []): User
    {
        $this->staff = BasicInformation::factory()->create($attributes);

        return User::factory()->create([
            'basic_information_ulid' => $this->staff->ulid,
            'role' => 'User',
        ]);
    }

    /* ----------------------------------------- performance appraisal */

    public function test_the_appraisal_page_carries_their_standing(): void
    {
        $user = $this->officer([
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'grade_level' => 9, 'step' => 4,
            'date_of_birth' => '1980-05-10', 'date_of_employment' => '2008-01-04',
        ]);

        $response = $this->actingAs($user)->get(route('my-appraisal'));

        $response->assertOk();
        $response->assertSee('Asuquo');
        $response->assertSee('GL 09');
        $response->assertSee('Step 4');
        // 60 years from the 1980 birth comes before 35 years from 2008.
        $response->assertSee('2040-05-10');
        $response->assertSee('Retirement by Age');
        $response->assertSee('30 days a year');
    }

    public function test_the_appraisal_page_says_where_they_are_posted(): void
    {
        $user = $this->officer();

        $state = State::where('name', 'Cross River')->firstOrFail();
        $lga = Lga::where('state_id', $state->id)->firstOrFail();

        Posting::create([
            'basic_information_ulid' => $this->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Registration Officer',
        ]);

        $this->actingAs($user)->get(route('my-appraisal'))
            ->assertSee($lga->lga_name);
    }

    public function test_an_officer_not_yet_assessed_is_told_so(): void
    {
        $this->actingAs($this->officer())->get(route('my-appraisal'))
            ->assertSee('You have not been assessed yet.');
    }

    /* ------------------------------------------------------------ leave */

    public function test_an_officer_applies_for_their_own_leave(): void
    {
        $user = $this->officer(['grade_level' => 5]);

        $this->actingAs($user)->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'reason' => 'Family matters',
        ])->assertRedirect(route('my-leave'));

        $application = LeaveApplication::first();

        $this->assertSame($this->staff->ulid, $application->basic_information_ulid);
        // GL 5 carries 21 days, which is not theirs to set.
        $this->assertSame(21, $application->days);
        $this->assertSame('2026-11-23', $application->resumption_date);
        $this->assertSame('pending', $application->status);
    }

    public function test_the_officer_applying_is_taken_from_the_session_not_the_form(): void
    {
        $user = $this->officer(['grade_level' => 5]);
        $someoneElse = BasicInformation::factory()->create(['grade_level' => 12]);

        $this->actingAs($user)->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            // Naming another officer changes nothing.
            'basic_information_ulid' => $someoneElse->ulid,
            'days' => 300,
        ]);

        $application = LeaveApplication::first();

        $this->assertSame($this->staff->ulid, $application->basic_information_ulid);
        $this->assertSame(21, $application->days);
    }

    public function test_an_officer_sees_only_their_own_applications(): void
    {
        $user = $this->officer(['surname' => 'Mine', 'grade_level' => 5]);
        $other = BasicInformation::factory()->create(['surname' => 'Theirs', 'grade_level' => 5]);

        LeaveApplication::create([
            'basic_information_ulid' => $this->staff->ulid,
            'leave_type' => 'Annual Leave', 'effective_date' => '2026-11-02', 'days' => 21,
        ]);
        LeaveApplication::create([
            'basic_information_ulid' => $other->ulid,
            'leave_type' => 'Study Leave', 'effective_date' => '2026-12-01', 'days' => 30,
        ]);

        // Every leave type is on the form's dropdown, so the listing itself is
        // what has to be read rather than the page as a whole.
        $listed = $this->actingAs($user)->get(route('my-leave'))->viewData('applications');

        $this->assertSame([$this->staff->ulid],
            $listed->pluck('basic_information_ulid')->unique()->values()->all());
        $this->assertSame(1, $listed->total());
    }

    public function test_the_status_of_an_application_is_shown(): void
    {
        $user = $this->officer(['grade_level' => 5]);

        LeaveApplication::create([
            'basic_information_ulid' => $this->staff->ulid,
            'leave_type' => 'Annual Leave', 'effective_date' => '2026-11-02',
            'days' => 21, 'status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('my-leave'))->assertSee('Approved');
    }

    public function test_an_officer_with_no_grade_level_cannot_apply(): void
    {
        $user = $this->officer(['grade_level' => null]);

        $this->actingAs($user)->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('leave_type');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_the_day_count_is_only_fixed_for_annual_leave(): void
    {
        // See LeaveDayCountTest: the entitlement is an *annual* leave
        // entitlement, so the field is readonly only when that is the type
        // chosen, and the page decides which.
        $this->actingAs($this->officer(['grade_level' => 5]))->get(route('my-leave'))
            ->assertDontSee('readonly data-days', false)
            ->assertSee('data-entitlement="21"', false);
    }

    /* -------------------------------------------------------- training */

    public function test_available_trainings_are_listed(): void
    {
        Training::factory()->create(['title' => 'Records Management Workshop']);

        $this->actingAs($this->officer())->get(route('my-training'))
            ->assertSee('Records Management Workshop')
            ->assertSee('Indicate Interest');
    }

    public function test_an_officer_puts_their_name_down(): void
    {
        $user = $this->officer();
        $training = Training::factory()->create();

        $this->actingAs($user)->post(route('my-training.interest'), [
            'training_ulid' => $training->ulid,
        ])->assertRedirect();

        $this->assertDatabaseHas('training_interests', [
            'training_id' => $training->id,
            'basic_information_ulid' => $this->staff->ulid,
        ]);
    }

    public function test_putting_a_name_down_twice_takes_it_off_again(): void
    {
        $user = $this->officer();
        $training = Training::factory()->create();

        // The button is a toggle, so a change of mind needs no second form.
        $this->actingAs($user)->post(route('my-training.interest'), ['training_ulid' => $training->ulid]);
        $this->assertSame(1, TrainingInterest::count());

        $this->actingAs($user)->post(route('my-training.interest'), ['training_ulid' => $training->ulid]);
        $this->assertSame(0, TrainingInterest::count());
    }

    public function test_a_training_already_wanted_shows_as_such(): void
    {
        $user = $this->officer();
        $training = Training::factory()->create();

        TrainingInterest::create([
            'training_id' => $training->id,
            'basic_information_ulid' => $this->staff->ulid,
        ]);

        $this->actingAs($user)->get(route('my-training'))
            ->assertSee('Your name is down')
            ->assertSee('Withdraw Interest');
    }

    public function test_a_closed_training_takes_no_more_names(): void
    {
        $user = $this->officer();
        $training = Training::factory()->closed()->create();

        $this->actingAs($user)->post(route('my-training.interest'), [
            'training_ulid' => $training->ulid,
        ])->assertSessionHasErrors('training_ulid');

        $this->assertSame(0, TrainingInterest::count());
    }

    /* ---------------------------------------------------- bio-data */

    public function test_the_bio_data_page_shows_their_own_record(): void
    {
        $user = $this->officer([
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'phone_number' => '08030000001', 'pfa' => 'Stanbic IBTC',
        ]);

        $response = $this->actingAs($user)->get(route('my-bio-data'));

        $response->assertSee('Asuquo');
        $response->assertSee('08030000001');
        $response->assertSee('Stanbic IBTC');
    }

    public function test_the_bio_data_page_shows_nobody_elses_record(): void
    {
        $user = $this->officer(['surname' => 'Mine']);
        BasicInformation::factory()->create(['surname' => 'Theirs']);

        $this->actingAs($user)->get(route('my-bio-data'))->assertDontSee('Theirs');
    }

    /* ------------------------------------------------------- photo */

    public function test_an_officer_uploads_their_photograph(): void
    {
        Storage::fake();

        $user = $this->officer();

        $this->actingAs($user)->post(route('my-photo.store'), [
            'photo' => UploadedFile::fake()->image('passport.jpg'),
        ])->assertRedirect(route('my-photo'));

        $this->staff->refresh();

        $this->assertNotNull($this->staff->photo_path);
        $this->assertSame('passport.jpg', $this->staff->photo_name);
        Storage::assertExists($this->staff->photo_path);
    }

    public function test_a_new_photograph_replaces_the_one_before_it(): void
    {
        Storage::fake();

        $user = $this->officer();

        $this->actingAs($user)->post(route('my-photo.store'), [
            'photo' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $first = $this->staff->fresh()->photo_path;

        $this->actingAs($user)->post(route('my-photo.store'), [
            'photo' => UploadedFile::fake()->image('second.jpg'),
        ]);

        $this->assertSame('second.jpg', $this->staff->fresh()->photo_name);
        // The one it replaced is of no further use.
        Storage::assertMissing($first);
    }

    public function test_a_document_is_not_a_photograph(): void
    {
        Storage::fake();

        $this->actingAs($this->officer())->post(route('my-photo.store'), [
            'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_a_photograph_is_handed_back_from_behind_the_login(): void
    {
        Storage::fake();

        $user = $this->officer();

        $this->actingAs($user)->post(route('my-photo.store'), [
            'photo' => UploadedFile::fake()->image('passport.jpg'),
        ]);

        $this->actingAs($user)->get(route('my-photo.show'))->assertOk();

        // Not to be had without signing in.
        auth()->logout();
        $this->get(route('my-photo.show'))->assertRedirect(route('login'));
    }

    /* --------------------------------------------- voluntary retirement */

    public function test_an_officer_applies_to_retire_early(): void
    {
        $user = $this->officer();

        $this->actingAs($user)->post(route('my-retirement.store'), [
            'intended_date' => now()->addMonths(6)->toDateString(),
            'reason' => 'To take up a family business.',
        ])->assertRedirect(route('my-retirement'));

        $request = VoluntaryRetirementRequest::first();

        $this->assertSame($this->staff->ulid, $request->basic_information_ulid);
        $this->assertSame('pending', $request->status);
    }

    public function test_applying_does_not_retire_the_officer(): void
    {
        $user = $this->officer();

        $this->actingAs($user)->post(route('my-retirement.store'), [
            'intended_date' => now()->addMonths(6)->toDateString(),
            'reason' => 'To take up a family business.',
        ]);

        // A request is not an exit: nothing changes until it is approved.
        $this->assertSame(0, $this->staff->fresh()->is_retired);
    }

    public function test_the_intended_date_must_be_still_to_come(): void
    {
        $this->actingAs($this->officer())->post(route('my-retirement.store'), [
            'intended_date' => now()->subDay()->toDateString(),
            'reason' => 'Backdated.',
        ])->assertSessionHasErrors('intended_date');

        $this->assertSame(0, VoluntaryRetirementRequest::count());
    }

    public function test_only_one_application_may_be_waiting_at_a_time(): void
    {
        $user = $this->officer();

        $payload = [
            'intended_date' => now()->addMonths(6)->toDateString(),
            'reason' => 'To take up a family business.',
        ];

        $this->actingAs($user)->post(route('my-retirement.store'), $payload);
        $this->actingAs($user)->post(route('my-retirement.store'), $payload)
            ->assertSessionHasErrors('intended_date');

        $this->assertSame(1, VoluntaryRetirementRequest::count());
    }

    public function test_an_officer_sees_only_their_own_retirement_applications(): void
    {
        $user = $this->officer();
        $other = BasicInformation::factory()->create();

        VoluntaryRetirementRequest::create([
            'basic_information_ulid' => $this->staff->ulid,
            'intended_date' => '2027-06-01', 'reason' => 'Mine to give.',
        ]);
        VoluntaryRetirementRequest::create([
            'basic_information_ulid' => $other->ulid,
            'intended_date' => '2027-07-01', 'reason' => 'Theirs to give.',
        ]);

        $response = $this->actingAs($user)->get(route('my-retirement'));

        $response->assertSee('Mine to give.');
        $response->assertDontSee('Theirs to give.');
    }

    /* --------------------------------------------------- change password */

    public function test_an_officer_changes_their_own_password(): void
    {
        $user = $this->officer();

        $this->actingAs($user)->post(route('change-password.store'), [
            'current_password' => 'password',
            'password' => 'a-longer-secret',
            'password_confirmation' => 'a-longer-secret',
        ])->assertRedirect(route('change-password'));

        $this->assertTrue(\Hash::check('a-longer-secret', $user->fresh()->password));
    }

    public function test_the_current_password_has_to_be_right(): void
    {
        $user = $this->officer();

        // A session left open on an unattended desk should not be enough to
        // lock its owner out.
        $this->actingAs($user)->post(route('change-password.store'), [
            'current_password' => 'not-it',
            'password' => 'a-longer-secret',
            'password_confirmation' => 'a-longer-secret',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(\Hash::check('password', $user->fresh()->password));
    }

    public function test_the_two_new_passwords_have_to_match(): void
    {
        $this->actingAs($this->officer())->post(route('change-password.store'), [
            'current_password' => 'password',
            'password' => 'a-longer-secret',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');
    }
}
