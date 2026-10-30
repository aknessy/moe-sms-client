<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\LeaveApplication;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\Setting;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A leave application read as the letter it would be on paper: the command's
 * letterhead, where the officer writes from, the desks it passes on the way to
 * the Commissioner, and a rule for them to sign.
 */
class LeaveLetterTest extends TestCase
{
    use RefreshDatabase;

    private function organisationOnFile(): void
    {
        Setting::putMany([
            'organisation.name' => 'Independent National Electoral Commission',
            'organisation.command' => 'Cross River State Office',
            'organisation.address' => '4 Murtala Mohammed Highway, Calabar',
            'organisation.telephone' => '08031234567',
            'organisation.email' => 'crossriver@inecnigeria.org',
        ]);
    }

    private function applicant(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey', 'othernames' => 'Eno',
            'department' => 'Operations', 'designation' => 'Administrative Officer',
            'grade_level' => 9, 'step' => 4,
        ]);
    }

    private function apply(BasicInformation $staff, array $overrides = []): LeaveApplication
    {
        return LeaveApplication::create(array_merge([
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 30,
            'reason' => 'To attend to a family matter in the village.',
        ], $overrides));
    }

    private function account(BasicInformation $staff, string $role = 'User'): User
    {
        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
    }

    /* ------------------------------------------------------ letterhead */

    public function test_the_letterhead_comes_from_the_settings(): void
    {
        $this->organisationOnFile();

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $response = $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid));

        $response->assertOk();
        $response->assertSee('Independent National Electoral Commission');
        $response->assertSee('Cross River State Office');
        $response->assertSee('4 Murtala Mohammed Highway, Calabar');
        $response->assertSee('08031234567');
        $response->assertSee('crossriver@inecnigeria.org');
    }

    public function test_a_telephone_not_recorded_is_simply_absent(): void
    {
        // "if saved" — an empty setting prints nothing rather than a label
        // with nothing after it.
        Setting::putMany(['organisation.telephone' => '']);

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertDontSee('Tel:');
    }

    /* --------------------------------------------------- the addressing */

    public function test_it_is_addressed_to_the_commissioner(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('The Resident Electoral Commissioner');
    }

    public function test_the_title_addressed_to_can_be_changed(): void
    {
        Setting::put('organisation.commissioner_title', 'The National Commissioner');

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('The National Commissioner');
    }

    public function test_it_goes_through_the_administrative_secretary(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('The Administrative Secretary');
    }

    public function test_at_headquarters_it_goes_through_the_head_of_department(): void
    {
        DepartmentHead::updateOrCreate(['department' => 'Operations'], ['role' => 'HOD EOPs']);

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('The Head of Department, Operations');
    }

    public function test_away_from_headquarters_it_goes_through_the_electoral_officer(): void
    {
        $state = State::where('name', config('posting.default_state'))->firstOrFail();
        $lga = Lga::where('state_id', $state->id)
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->firstOrFail();

        $staff = $this->applicant();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Registration Officer',
        ]);

        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee("The Electoral Officer, {$lga->lga_name} LGA");
    }

    /* ------------------------------------------------- what it says */

    public function test_the_heading_says_what_is_applied_for(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff, ['leave_type' => 'Casual Leave', 'days' => 14]);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('APPLICATION FOR CASUAL LEAVE');
    }

    public function test_the_body_carries_the_dates_and_the_day_count(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $response = $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid));

        $response->assertSee('30 working days');
        $response->assertSee('2 November 2026');
        // Thirty days from the 2nd; back on 2 December.
        $response->assertSee('2 December 2026');
    }

    public function test_the_officers_own_words_are_in_the_letter(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('To attend to a family matter in the village.');
    }

    public function test_the_date_it_was_made_is_on_it(): void
    {
        $this->travelTo('2026-10-14 09:00:00');

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertSee('14 October 2026');
    }

    public function test_where_the_officer_writes_from_is_on_it(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $response = $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid));

        $response->assertSee('Operations');
        // Falls back to this command where no posting says otherwise.
        $response->assertSee(config('posting.default_station'));
    }

    public function test_the_signature_block_names_the_officer(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $response = $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid));

        $response->assertSee('Yours faithfully');
        $response->assertSee('Asuquo, Bassey Eno');
        $response->assertSee('GL 09 / Step 4');
        $response->assertSee($staff->staff_number);
    }

    /* --------------------------------------------------- who may read it */

    public function test_an_officer_may_read_their_own_letter(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('my-leave.show', $application->ulid))
            ->assertOk();
    }

    public function test_an_officer_may_not_read_somebody_elses(): void
    {
        $mine = $this->applicant();
        $theirs = BasicInformation::factory()->create(['surname' => 'Other', 'grade_level' => 7]);

        $application = $this->apply($theirs);

        // The ulid is not a way into anyone else's record.
        $this->actingAs($this->account($mine))
            ->get(route('my-leave.show', $application->ulid))
            ->assertNotFound();
    }

    public function test_a_reviewer_may_read_any_letter(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $reviewer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);

        $this->actingAs($reviewer)->get(route('hr-leave.show', $application->ulid))
            ->assertOk()
            ->assertSee('APPLICATION FOR ANNUAL LEAVE');
    }

    public function test_an_officer_cannot_reach_the_reviewers_view(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))
            ->get(route('hr-leave.show', $application->ulid))
            ->assertForbidden();
    }

    public function test_opening_the_letter_clears_the_notice_about_it(): void
    {
        $admin = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create(['surname' => 'Admin'])->ulid,
            'role' => 'Administrator',
        ]);

        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->assertSame(1, \App\Models\Notification::for($admin)->unread()->count());

        $this->actingAs($admin)->get(route('hr-leave.show', $application->ulid));

        $this->assertSame(0, \App\Models\Notification::for($admin)->unread()->count());
    }

    /* ------------------------------------------------------- the listing */

    public function test_the_listing_links_to_the_letter(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $this->actingAs($this->account($staff))->get(route('my-leave'))
            ->assertSee(route('my-leave.show', $application->ulid));
    }

    public function test_the_action_column_is_no_longer_a_dead_button(): void
    {
        $staff = $this->applicant();
        $application = $this->apply($staff);

        $reviewer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);

        $this->actingAs($reviewer)->get(route('hr-leave'))
            ->assertSee(route('hr-leave.show', $application->ulid))
            ->assertSee('View Letter');
    }
}
