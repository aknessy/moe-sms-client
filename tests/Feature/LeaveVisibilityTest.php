<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Who sees whose leave.
 *
 * An administrator is an officer too — they apply for their own leave on the
 * same page as anyone else. So the page carries two tables: their own, and
 * everyone else's. An officer on the `User` role sees only the first.
 */
class LeaveVisibilityTest extends TestCase
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
        return BasicInformation::factory()->create($attributes + ['grade_level' => 5]);
    }

    private function apply(BasicInformation $staff, array $overrides = []): LeaveApplication
    {
        return LeaveApplication::create(array_merge([
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 21,
        ], $overrides));
    }

    /* ------------------------------------------- the two tables */

    public function test_an_administrator_sees_their_own_and_everyone_elses(): void
    {
        $mine = $this->officer(['surname' => 'Mine']);
        $admin = $this->accountFor($mine, 'Administrator');

        $this->apply($mine);
        $this->apply($this->officer(['surname' => 'Theirs']));

        $response = $this->actingAs($admin)->get(route('my-leave'));

        $this->assertSame(1, $response->viewData('applications')->total());
        $this->assertSame(1, $response->viewData('others')->total());

        $response->assertSee('My Leave Applications');
        $response->assertSee("Other Staff's Leave Applications");
        $response->assertSee('Theirs');
    }

    public function test_an_administrators_own_application_is_not_in_the_other_table(): void
    {
        $mine = $this->officer(['surname' => 'Mine']);
        $admin = $this->accountFor($mine, 'Administrator');

        $this->apply($mine);

        // It belongs above, with their own, and must not be counted twice.
        $this->assertSame(0, $this->actingAs($admin)->get(route('my-leave'))
            ->viewData('others')->total());
    }

    public function test_an_officer_on_the_user_role_has_no_second_table(): void
    {
        $mine = $this->officer(['surname' => 'Mine']);
        $account = $this->accountFor($mine, 'User');

        $this->apply($this->officer(['surname' => 'Theirs']));

        $response = $this->actingAs($account)->get(route('my-leave'));

        $this->assertNull($response->viewData('others'));
        $response->assertDontSee("Other Staff's Leave Applications");
        $response->assertDontSee('Theirs');
    }

    public function test_the_other_table_shows_both_pending_and_decided(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'Waiting']));
        $this->apply($this->officer(['surname' => 'Granted']), ['status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('my-leave'));

        $response->assertSee('Waiting');
        $response->assertSee('Granted');
        $response->assertSee('Approved');
        $response->assertSee('Pending');
    }

    /* ------------------------------------- filtering the full listing */

    public function test_human_resources_lists_every_application(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'Waiting']));
        $this->apply($this->officer(['surname' => 'Granted']), ['status' => 'approved']);

        $this->assertSame(2, $this->actingAs($admin)->get(route('hr-leave'))
            ->viewData('applications')->total());
    }

    public static function statusesAndTheirCounts(): array
    {
        return [
            'pending' => ['pending', 'Waiting'],
            'approved' => ['approved', 'Granted'],
            'declined' => ['declined', 'Refused'],
        ];
    }

    #[DataProvider('statusesAndTheirCounts')]
    public function test_the_listing_can_be_narrowed_to_one_standing(string $status, string $surname): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'Waiting']));
        $this->apply($this->officer(['surname' => 'Granted']), ['status' => 'approved']);
        $this->apply($this->officer(['surname' => 'Refused']), ['status' => 'declined']);

        $listed = $this->actingAs($admin)->get(route('hr-leave', ['status' => $status]))
            ->viewData('applications');

        $this->assertSame(1, $listed->total());
        $this->assertSame($surname, $listed->first()->staff->surname);
    }

    public function test_a_standing_that_is_not_one_shows_the_lot(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));
        $this->apply($this->officer(['surname' => 'Two']), ['status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('hr-leave', ['status' => 'anything']));

        $this->assertSame(2, $response->viewData('applications')->total());
        $this->assertNull($response->viewData('status'));
    }

    public function test_the_tabs_count_what_is_behind_them(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));
        $this->apply($this->officer(['surname' => 'Two']));
        $this->apply($this->officer(['surname' => 'Three']), ['status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('hr-leave'));

        $this->assertSame(3, $response->viewData('total'));
        $this->assertSame(2, $response->viewData('counts')['pending']);
        $this->assertSame(1, $response->viewData('counts')['approved']);
    }

    public function test_an_empty_filter_says_which_filter_is_empty(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'Waiting']));

        $this->actingAs($admin)->get(route('hr-leave', ['status' => 'approved']))
            ->assertSee('No applications are approved.');
    }

    /* --------------------------------------------------------- the bell */

    public function test_the_bell_shows_what_is_waiting(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));

        $html = $this->actingAs($admin)->get(route('hr-leave'))->getContent();

        $this->assertMatchesRegularExpression('/bg-error[^>]*>\s*1\s*<\/span>/', $html);
    }

    public function test_the_bell_carries_no_badge_when_nothing_is_waiting(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $html = $this->actingAs($admin)->get(route('hr-leave'))->getContent();

        // The bell is always there; the badge only when something is unread.
        $this->assertStringContainsString(route('notifications'), $html);
        $this->assertDoesNotMatchRegularExpression('/bg-error[^>]*>\s*\d/', $html);
    }

    public function test_the_bell_counts_notices_of_any_kind(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));

        // Not every notice is about leave.
        \App\Models\Notification::send([$admin], 'Something else entirely');

        $html = $this->actingAs($admin)->get(route('hr-leave'))->getContent();

        $this->assertMatchesRegularExpression('/bg-error[^>]*>\s*2\s*<\/span>/', $html);
    }
}
