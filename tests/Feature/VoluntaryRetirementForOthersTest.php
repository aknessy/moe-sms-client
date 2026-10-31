<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use App\Models\VoluntaryRetirementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Raising a request to leave the service early.
 *
 * An officer applies for themselves and no one else. Whoever has the run of
 * the system may raise one for whichever officer walks in with the paperwork,
 * and sees what everyone else has asked for.
 */
class VoluntaryRetirementForOthersTest extends TestCase
{
    use RefreshDatabase;

    private function accountFor(BasicInformation $staff, string $role): User
    {
        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes);
    }

    private function application(array $overrides = []): array
    {
        return array_merge([
            'intended_date' => now()->addMonths(6)->toDateString(),
            'reason' => 'To take up a family business.',
        ], $overrides);
    }

    /* --------------------------------------------- the form on offer */

    public function test_an_administrator_is_offered_the_staff_picker(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $this->officer(['surname' => 'Eligible']);

        $response = $this->actingAs($admin)->get(route('my-retirement'));

        $response->assertOk();
        $this->assertTrue($response->viewData('forOthers'));
        $response->assertSee('data-staff-picker', false);
        $response->assertSee('Eligible');
    }

    public function test_an_officer_is_offered_no_picker(): void
    {
        $account = $this->accountFor($this->officer(['surname' => 'Mine']), 'User');
        $this->officer(['surname' => 'Somebody']);

        $response = $this->actingAs($account)->get(route('my-retirement'));

        $this->assertFalse($response->viewData('forOthers'));
        $response->assertDontSee('data-staff-picker', false);
        $response->assertDontSee('Somebody');
    }

    public function test_only_officers_in_active_service_are_offered(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->officer(['surname' => 'Serving']);
        $this->officer(['surname' => 'Retired', 'is_retired' => 1]);
        $this->officer(['surname' => 'Departed', 'is_deceased' => 1]);

        $offered = $this->actingAs($admin)->get(route('my-retirement'))
            ->viewData('staffOptions')->implode(' ');

        $this->assertStringContainsString('Serving', $offered);
        $this->assertStringNotContainsString('Retired', $offered);
        $this->assertStringNotContainsString('Departed', $offered);
    }

    /* ------------------------------------------ raising one for another */

    public function test_an_administrator_applies_on_an_officers_behalf(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo']);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $officer->ulid]))
            ->assertRedirect(route('my-retirement'));

        $this->assertSame($officer->ulid,
            VoluntaryRetirementRequest::first()->basic_information_ulid);
    }

    public function test_an_administrator_still_applies_for_themselves(): void
    {
        $theirs = $this->officer(['surname' => 'Admin']);
        $admin = $this->accountFor($theirs, 'Administrator');

        // Naming nobody means naming themselves.
        $this->actingAs($admin)->post(route('my-retirement.store'), $this->application())
            ->assertRedirect(route('my-retirement'));

        $this->assertSame($theirs->ulid,
            VoluntaryRetirementRequest::first()->basic_information_ulid);
    }

    public function test_an_officer_cannot_apply_on_anybody_elses_behalf(): void
    {
        $theirs = $this->officer(['surname' => 'Mine']);
        $account = $this->accountFor($theirs, 'User');
        $somebody = $this->officer(['surname' => 'Somebody']);

        // Adding the field to the form by hand changes nothing.
        $this->actingAs($account)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $somebody->ulid]));

        $this->assertSame($theirs->ulid,
            VoluntaryRetirementRequest::first()->basic_information_ulid);
    }

    public function test_an_officer_out_of_the_service_cannot_be_applied_for(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $retired = $this->officer(['surname' => 'Retired', 'is_retired' => 1]);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $retired->ulid]))
            ->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(0, VoluntaryRetirementRequest::count());
    }

    public function test_an_officer_who_does_not_exist_cannot_be_applied_for(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => 'NOTAREALULID0000000000000']))
            ->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_the_one_at_a_time_rule_follows_the_officer_applied_for(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo']);

        $payload = $this->application(['basic_information_ulid' => $officer->ulid]);

        $this->actingAs($admin)->post(route('my-retirement.store'), $payload);
        $this->actingAs($admin)->post(route('my-retirement.store'), $payload)
            ->assertSessionHasErrors('intended_date');

        $this->assertSame(1, VoluntaryRetirementRequest::count());
    }

    public function test_one_officers_pending_request_does_not_block_another(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $first = $this->officer(['surname' => 'First']);
        $second = $this->officer(['surname' => 'Second']);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $first->ulid]));
        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $second->ulid]));

        $this->assertSame(2, VoluntaryRetirementRequest::count());
    }

    public function test_an_administrator_with_a_pending_request_may_still_apply_for_others(): void
    {
        // The form is not only about them, so it stays on the page.
        $theirs = $this->officer(['surname' => 'Admin']);
        $admin = $this->accountFor($theirs, 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo']);

        $this->actingAs($admin)->post(route('my-retirement.store'), $this->application());

        $this->actingAs($admin)->get(route('my-retirement'))
            ->assertSee('data-staff-picker', false);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $officer->ulid]));

        $this->assertSame(2, VoluntaryRetirementRequest::count());
    }

    public function test_an_officer_with_a_pending_request_is_offered_no_form(): void
    {
        $account = $this->accountFor($this->officer(['surname' => 'Mine']), 'User');

        $this->actingAs($account)->post(route('my-retirement.store'), $this->application());

        $this->actingAs($account)->get(route('my-retirement'))
            ->assertSee('You already have an application awaiting a decision.');
    }

    /* ------------------------------------------------------ the tables */

    public function test_my_applications_shows_only_the_signed_in_officers(): void
    {
        $theirs = $this->officer(['surname' => 'Admin']);
        $admin = $this->accountFor($theirs, 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo']);

        $this->actingAs($admin)->post(route('my-retirement.store'), $this->application());
        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $officer->ulid]));

        $response = $this->actingAs($admin)->get(route('my-retirement'));

        $this->assertSame([$theirs->ulid],
            $response->viewData('requests')->pluck('basic_information_ulid')->all());
        $this->assertSame([$officer->ulid],
            $response->viewData('others')->pluck('basic_information_ulid')->all());
    }

    public function test_the_second_table_lists_who_has_applied(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo', 'firstname' => 'Bassey',
                                   'grade_level' => 12, 'department' => 'Legal & Litigation']);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $officer->ulid]));

        $response = $this->actingAs($admin)->get(route('my-retirement'));

        $response->assertSee('Staff Applications for Voluntary Retirement');
        $response->assertSee('Eyo');
        $response->assertSee('GL 12');
        $response->assertSee('Legal & Litigation');
        $response->assertSee('Pending');
    }

    public function test_an_officer_has_no_second_table(): void
    {
        $account = $this->accountFor($this->officer(['surname' => 'Mine']), 'User');
        $somebody = $this->officer(['surname' => 'Somebody']);

        VoluntaryRetirementRequest::create([
            'basic_information_ulid' => $somebody->ulid,
            'intended_date' => '2027-06-01', 'reason' => 'Theirs to give.',
        ]);

        $response = $this->actingAs($account)->get(route('my-retirement'));

        $this->assertNull($response->viewData('others'));
        $response->assertDontSee('Staff Applications for Voluntary Retirement');
        $response->assertDontSee('Theirs to give.');
    }

    public function test_applying_for_somebody_does_not_retire_them(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $officer = $this->officer(['surname' => 'Eyo']);

        $this->actingAs($admin)->post(route('my-retirement.store'),
            $this->application(['basic_information_ulid' => $officer->ulid]));

        // A request is not an exit.
        $this->assertSame(0, $officer->fresh()->is_retired);
    }
}
