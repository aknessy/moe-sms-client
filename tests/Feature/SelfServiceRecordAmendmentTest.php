<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\RecordAmendment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An officer raising a correction against their own record. The request
 * waits on the Registry the same way one raised through the Registry's own
 * form does — this only gives an officer a way in without needing to ask
 * someone else to type it for them.
 */
class SelfServiceRecordAmendmentTest extends TestCase
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

    private function submit(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('my-record-amendment.store'), array_merge([
            'field' => 'phone_number',
            'current_value' => '08030000000',
            'requested_value' => '08031111111',
            'reason' => 'The number on file is an old line.',
        ], $overrides));
    }

    public function test_the_page_shows_the_form_and_their_own_requests(): void
    {
        $user = $this->officer(['surname' => 'Asuquo']);

        RecordAmendment::create([
            'basic_information_ulid' => $this->staff->ulid,
            'field' => 'phone_number',
            'current_value' => '08030000000',
            'requested_value' => '08031111111',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->get(route('my-record-amendment'))
            ->assertOk()
            ->assertSee('Something Incorrect?')
            ->assertSee('08031111111');
    }

    public function test_an_officer_sees_only_their_own_requests(): void
    {
        $user = $this->officer();
        $somebodyElse = BasicInformation::factory()->create();

        RecordAmendment::create([
            'basic_information_ulid' => $somebodyElse->ulid,
            'field' => 'phone_number',
            'requested_value' => '08039999999',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->get(route('my-record-amendment'))
            ->assertDontSee('08039999999');
    }

    public function test_a_request_is_raised_against_their_own_record(): void
    {
        $user = $this->officer();

        $this->submit($user)->assertRedirect(route('my-record-amendment'));

        $request = RecordAmendment::sole();

        $this->assertSame($this->staff->ulid, $request->basic_information_ulid);
        $this->assertSame('phone_number', $request->field);
        $this->assertSame('08031111111', $request->requested_value);
        $this->assertSame('pending', $request->status);
    }

    public function test_a_field_outside_the_vocabulary_is_refused(): void
    {
        $user = $this->officer();

        $this->submit($user, ['field' => 'password'])
            ->assertSessionHasErrors('field');

        $this->assertSame(0, RecordAmendment::count());
    }

    public function test_the_requested_value_is_required(): void
    {
        $user = $this->officer();

        $this->submit($user, ['requested_value' => ''])
            ->assertSessionHasErrors('requested_value');
    }

    public function test_a_grade_level_request_must_be_a_number(): void
    {
        $user = $this->officer();

        $this->submit($user, ['field' => 'grade_level', 'requested_value' => 'Senior'])
            ->assertSessionHasErrors('requested_value');

        $this->assertSame(0, RecordAmendment::count());
    }

    public function test_a_grade_level_request_given_as_a_number_is_accepted(): void
    {
        $user = $this->officer();

        $this->submit($user, ['field' => 'grade_level', 'requested_value' => '9'])
            ->assertRedirect(route('my-record-amendment'));

        $this->assertSame('9', RecordAmendment::sole()->requested_value);
    }

    public function test_the_page_is_reached_from_bio_data(): void
    {
        $user = $this->officer();

        $this->actingAs($user)->get(route('my-bio-data'))
            ->assertSee(route('my-record-amendment'));
    }
}
