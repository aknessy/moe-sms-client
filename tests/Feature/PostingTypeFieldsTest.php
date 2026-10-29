<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffController;
use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A transfer moves an officer to another state; a deployment moves them
 * within the one they are already in. Each is recorded differently.
 */
class PostingTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function state(string $name): State
    {
        return State::where('name', 'like', $name . '%')->firstOrFail();
    }

    private function lgaIn(State $state): Lga
    {
        return Lga::where('state_id', $state->id)->firstOrFail();
    }

    /** An officer whose last transfer left them in the given state. */
    private function officerIn(State $state, string $station = 'Calabar HQ'): BasicInformation
    {
        $staff = BasicInformation::factory()->create();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $state->id,
            'to_lga' => $this->lgaIn($state)->id,
            'duty_station' => $station,
            'effective_date' => '2020-01-01',
        ]);

        return $staff;
    }

    private function record(array $payload)
    {
        return $this->actingAs($this->user())->post(route('posting-deployment.store'), $payload);
    }

    /* ------------------------------------------------------ posting types */

    public function test_only_transfer_and_deployment_are_offered(): void
    {
        $this->assertSame(['transfer', 'deployment'], array_keys(Posting::TYPES));
    }

    public function test_secondment_is_no_longer_a_posting_type(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'secondment',
            'effective_date' => '2026-01-01',
        ])->assertSessionHasErrors('posting_type');

        $this->assertSame(0, Posting::count());
    }

    public function test_the_form_does_not_offer_secondment(): void
    {
        $response = $this->actingAs($this->user())->get(route('posting-deployment'));

        $response->assertOk();
        $this->assertArrayNotHasKey('secondment', $response->viewData('postingTypes'));
    }

    /* ----------------------------------------------------------- transfer */

    public function test_a_transfer_records_the_state_and_lga_it_sends_them_to(): void
    {
        $lagos = $this->state('Lagos');
        $staff = $this->officerIn($this->state('Cross River'));
        $destination = $this->lgaIn($lagos);

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $destination->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
        ])->assertRedirect(route('posting-deployment'));

        $posting = Posting::latest('id')->first();

        $this->assertSame('transfer', $posting->posting_type);
        $this->assertSame($lagos->id, $posting->to_state);
        $this->assertSame($destination->id, $posting->to_lga);
        $this->assertSame('Ikeja Office', $posting->duty_station);
    }

    public function test_the_state_a_transfer_moves_them_out_of_is_taken_from_the_record(): void
    {
        $crossRiver = $this->state('Cross River');
        $lagos = $this->state('Lagos');
        $staff = $this->officerIn($crossRiver);

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            // What the readonly field held is not trusted; Kano is a lie.
            'from_state' => $this->state('Kano')->id,
            'to_state' => $lagos->id,
            'to_lga' => $this->lgaIn($lagos)->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
        ]);

        $this->assertSame($crossRiver->id, Posting::latest('id')->first()->from_state);
    }

    public function test_a_transfer_needs_a_state_an_lga_and_a_duty_station(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'effective_date' => '2026-02-01',
        ])->assertSessionHasErrors(['to_state', 'to_lga', 'duty_station']);
    }

    public function test_a_transfer_refuses_an_lga_outside_the_state_it_sends_them_to(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $this->state('Lagos')->id,
            // An LGA of another state entirely.
            'to_lga' => $this->lgaIn($this->state('Kano'))->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
        ])->assertSessionHasErrors('to_lga');

        $this->assertSame(0, Posting::where('posting_type', 'transfer')->count());
    }

    public function test_a_transfer_carries_no_deployment_detail(): void
    {
        $lagos = $this->state('Lagos');
        $staff = $this->officerIn($this->state('Cross River'));

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $this->lgaIn($lagos)->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
            // Submitted anyway; a transfer is not a deployment.
            'deployed_as' => 'Staff',
            'present_station' => 'Somewhere',
        ]);

        $posting = Posting::latest('id')->first();

        $this->assertNull($posting->deployed_as);
        $this->assertNull($posting->present_station);
    }

    /* --------------------------------------------------------- deployment */

    public function test_a_deployment_records_the_lga_and_what_they_are_deployed_as(): void
    {
        $crossRiver = $this->state('Cross River');
        $staff = $this->officerIn($crossRiver, 'Calabar HQ');
        $destination = Lga::where('state_id', $crossRiver->id)->skip(1)->first();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $destination->id,
            'deployed_as' => 'Electoral Officer (EO)',
            'effective_date' => '2026-03-01',
        ])->assertRedirect(route('posting-deployment'));

        $posting = Posting::latest('id')->first();

        $this->assertSame('deployment', $posting->posting_type);
        $this->assertSame($destination->id, $posting->to_lga);
        $this->assertSame('Electoral Officer (EO)', $posting->deployed_as);
        // Where they were, read from the record rather than the form.
        $this->assertSame('Calabar HQ', $posting->present_station);
        $this->assertNull($posting->to_state);
    }

    public function test_a_deployment_is_confined_to_the_state_the_officer_is_in(): void
    {
        $staff = $this->officerIn($this->state('Cross River'));

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            // An LGA of another state: a deployment cannot leave the state.
            'to_lga' => $this->lgaIn($this->state('Lagos'))->id,
            'deployed_as' => 'Staff',
            'effective_date' => '2026-03-01',
        ])->assertSessionHasErrors('to_lga');

        $this->assertSame(0, Posting::where('posting_type', 'deployment')->count());
    }

    public function test_a_deployment_needs_an_lga_and_a_role(): void
    {
        $staff = $this->officerIn($this->state('Cross River'));

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'effective_date' => '2026-03-01',
        ])->assertSessionHasErrors(['to_lga', 'deployed_as']);
    }

    public function test_a_deployment_refuses_a_role_outside_the_five(): void
    {
        $crossRiver = $this->state('Cross River');
        $staff = $this->officerIn($crossRiver);

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $this->lgaIn($crossRiver)->id,
            'deployed_as' => 'Resident Electoral Commissioner',
            'effective_date' => '2026-03-01',
        ])->assertSessionHasErrors('deployed_as');
    }

    public function test_the_five_things_an_officer_may_be_deployed_as(): void
    {
        $this->assertSame([
            'Electoral Officer (EO)',
            'Assistant Electoral Officer (AEO) RAC',
            'Assistant Electoral Officer (AEO) Admin',
            'Assistant Electoral Officer (AEO) Operations',
            'Staff',
        ], array_keys(Posting::DEPLOYMENT_ROLES));
    }

    public function test_a_deployment_needs_no_duty_station(): void
    {
        $crossRiver = $this->state('Cross River');
        $staff = $this->officerIn($crossRiver);

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $this->lgaIn($crossRiver)->id,
            'deployed_as' => 'Staff',
            'effective_date' => '2026-03-01',
        ])->assertRedirect(route('posting-deployment'));

        $this->assertNull(Posting::latest('id')->first()->duty_station);
    }

    public function test_an_officer_never_posted_may_be_deployed_within_this_command(): void
    {
        // Nothing on record, so they are taken to stand at this command and
        // can be deployed about it.
        $staff = BasicInformation::factory()->create();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $this->lgaIn($this->state(config('posting.default_state')))->id,
            'deployed_as' => 'Staff',
            'effective_date' => '2026-03-01',
        ])->assertRedirect(route('posting-deployment'));

        $this->assertSame(
            config('posting.default_station'),
            Posting::latest('id')->first()->present_station
        );
    }

    public function test_an_officer_never_posted_still_cannot_be_deployed_out_of_this_command(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            // Kano is not this command, and a deployment cannot leave it.
            'to_lga' => $this->lgaIn($this->state('Kano'))->id,
            'deployed_as' => 'Staff',
            'effective_date' => '2026-03-01',
        ])->assertSessionHasErrors('to_lga');
    }

    public function test_a_transfer_for_an_officer_never_posted_moves_them_out_of_this_command(): void
    {
        $staff = BasicInformation::factory()->create();
        $lagos = $this->state('Lagos');

        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $this->lgaIn($lagos)->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
        ])->assertRedirect(route('posting-deployment'));

        $this->assertSame(
            $this->state(config('posting.default_state'))->id,
            Posting::latest('id')->first()->from_state
        );
    }

    /* ------------------------------------------- where an officer stands */

    public function test_the_page_says_where_each_officer_stands(): void
    {
        $crossRiver = $this->state('Cross River');
        $staff = $this->officerIn($crossRiver, 'Calabar HQ');

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame($crossRiver->id, $context[$staff->ulid]['state_id']);
        $this->assertSame($crossRiver->name, $context[$staff->ulid]['state']);
        $this->assertSame('Calabar HQ', $context[$staff->ulid]['station']);
    }

    public function test_the_latest_transfer_is_the_state_they_are_in(): void
    {
        $lagos = $this->state('Lagos');
        $staff = $this->officerIn($this->state('Cross River'));

        // Transferred on again, so Lagos is where they now are.
        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $this->lgaIn($lagos)->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2024-06-01',
        ]);

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame($lagos->id, $context[$staff->ulid]['state_id']);
    }

    public function test_an_officer_never_posted_stands_at_this_command(): void
    {
        $staff = BasicInformation::factory()->create(['division' => 'Voter Registry']);

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame(StaffController::defaultPosting(), $context[$staff->ulid]);
    }

    public function test_this_command_is_a_real_state_lga_and_station(): void
    {
        $default = StaffController::defaultPosting();

        $this->assertSame(config('posting.default_state'), $default['state']);
        $this->assertSame(config('posting.default_lga'), $default['lga']);
        $this->assertSame(config('posting.default_station'), $default['station']);

        // Matched against the reference tables, not just echoed back.
        $this->assertSame($this->state(config('posting.default_state'))->id, $default['state_id']);
        $this->assertSame($default['state_id'], Lga::find($default['lga_id'])->state_id);
    }

    public function test_a_posting_on_record_wins_over_this_command(): void
    {
        $lagos = $this->state('Lagos');
        $staff = $this->officerIn($lagos, 'Ikeja Office');

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame($lagos->id, $context[$staff->ulid]['state_id']);
        $this->assertSame('Ikeja Office', $context[$staff->ulid]['station']);
        $this->assertNotSame(config('posting.default_station'), $context[$staff->ulid]['station']);
    }

    public function test_only_what_is_missing_falls_back(): void
    {
        $crossRiver = $this->state(config('posting.default_state'));
        $staff = BasicInformation::factory()->create();

        // A deployment: it sets an LGA and a station, but never a state.
        $lga = Lga::where('state_id', $crossRiver->id)->skip(3)->first();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'deployed_as' => 'Staff',
            'duty_station' => 'Obudu LGA Office',
            'effective_date' => '2025-01-01',
        ]);

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        // The LGA and station are their own; the state still comes from here.
        $this->assertSame($lga->id, $context[$staff->ulid]['lga_id']);
        $this->assertSame('Obudu LGA Office', $context[$staff->ulid]['station']);
        $this->assertSame($crossRiver->id, $context[$staff->ulid]['state_id']);
    }

    public function test_a_deployment_leaves_them_standing_at_the_lga_it_sent_them_to(): void
    {
        $crossRiver = $this->state(config('posting.default_state'));
        $staff = $this->officerIn($crossRiver, 'Calabar HQ');
        $destination = Lga::where('state_id', $crossRiver->id)->skip(7)->first();

        // A deployment names no duty station, so the LGA is the station.
        $this->record([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $destination->id,
            'deployed_as' => 'Electoral Officer (EO)',
            'effective_date' => '2026-04-01',
        ])->assertRedirect(route('posting-deployment'));

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame($destination->lga_name, $context[$staff->ulid]['station']);
    }

    public function test_deploying_twice_reads_the_first_posting_as_where_they_came_from(): void
    {
        $crossRiver = $this->state(config('posting.default_state'));
        $staff = BasicInformation::factory()->create();
        $lgas = Lga::where('state_id', $crossRiver->id)->take(2)->get();

        foreach ($lgas as $index => $lga) {
            $this->record([
                'basic_information_ulid' => $staff->ulid,
                'posting_type' => 'deployment',
                'to_lga' => $lga->id,
                'deployed_as' => 'Staff',
                'effective_date' => '2026-0' . ($index + 5) . '-01',
            ])->assertRedirect(route('posting-deployment'));
        }

        $postings = Posting::where('basic_information_ulid', $staff->ulid)
            ->orderBy('effective_date')->get();

        // The first moved them off this command's HQ; the second, out of the
        // LGA the first left them in.
        $this->assertSame(config('posting.default_station'), $postings[0]->present_station);
        $this->assertSame($lgas[0]->lga_name, $postings[1]->present_station);
    }

    public function test_the_lga_follows_the_last_posting_of_either_kind(): void
    {
        $crossRiver = $this->state(config('posting.default_state'));
        $staff = $this->officerIn($crossRiver);
        $later = Lga::where('state_id', $crossRiver->id)->skip(5)->first();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $later->id,
            'deployed_as' => 'Staff',
            'effective_date' => '2026-01-01',
        ]);

        $context = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('postingContext');

        $this->assertSame($later->id, $context[$staff->ulid]['lga_id']);
        $this->assertSame($later->lga_name, $context[$staff->ulid]['lga']);
    }

    public function test_the_page_carries_the_lgas_of_each_state(): void
    {
        $crossRiver = $this->state('Cross River');

        $byState = $this->actingAs($this->user())->get(route('posting-deployment'))
            ->viewData('lgasByState');

        $this->assertCount(18, $byState[$crossRiver->id]);
        $this->assertSame(37, $byState->count());
    }

    /* ----------------------------------------------- how it reads on screen */

    public function test_a_transfer_reads_as_the_state_and_lga_it_sent_them_to(): void
    {
        $lagos = $this->state('Lagos');
        $lga = $this->lgaIn($lagos);

        $posting = Posting::create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'posting_type' => 'transfer',
            'from_state' => $this->state('Cross River')->id,
            'to_state' => $lagos->id,
            'to_lga' => $lga->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2026-02-01',
        ]);

        $this->assertSame($this->state('Cross River')->name, $posting->origin);
        $this->assertSame($lagos->name . ' / ' . $lga->lga_name, $posting->destination);
    }

    public function test_a_deployment_reads_as_the_lga_and_the_role(): void
    {
        $lga = $this->lgaIn($this->state('Cross River'));

        $posting = Posting::create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'posting_type' => 'deployment',
            'present_station' => 'Calabar HQ',
            'to_lga' => $lga->id,
            'deployed_as' => 'Electoral Officer (EO)',
            'effective_date' => '2026-03-01',
        ]);

        $this->assertSame('Calabar HQ', $posting->origin);
        $this->assertSame($lga->lga_name . ' — Electoral Officer (EO)', $posting->destination);
    }

    public function test_a_posting_from_before_this_change_still_reads(): void
    {
        // Older rows carry departments and no state at all.
        $posting = Posting::create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'posting_type' => 'deployment',
            'from_department' => 'Operations',
            'to_department' => 'ICT & Voter Registry',
            'effective_date' => '2023-01-01',
        ]);

        $this->assertSame('Operations', $posting->origin);
        $this->assertSame('ICT & Voter Registry', $posting->destination);
    }
}
