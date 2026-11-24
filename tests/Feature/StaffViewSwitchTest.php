<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An administrator is a staff member first: they may work as one, through the
 * staff menu and with a staff account's reach, and switch back.
 */
class StaffViewSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => $role,
        ]);
    }

    public function test_an_administrator_is_offered_the_switch(): void
    {
        $this->actingAs($this->account('Administrator'))->get(route('dashboard'))
            ->assertSee('Switch to staff view');
    }

    public function test_nobody_else_is_offered_it(): void
    {
        foreach (['User', 'Registry Officer', 'HOD GAP'] as $role) {
            $this->actingAs($this->account($role))
                ->get(route($role === 'User' ? 'my-bio-data' : 'dashboard'))
                ->assertDontSee('Switch to staff view');
        }
    }

    public function test_switching_lands_on_the_staff_dashboard_with_the_staff_menu(): void
    {
        $admin = $this->account('Administrator');

        $this->actingAs($admin)->post(route('view.staff'))
            ->assertRedirect(route('my-bio-data'));

        $this->get(route('my-bio-data'))
            ->assertOk()
            ->assertSee('Voluntary Retirement')
            ->assertSee('Switch to administrator view')
            ->assertDontSee('Human Resources');
    }

    public function test_in_staff_view_the_administrator_is_kept_to_staff_pages(): void
    {
        $admin = $this->account('Administrator');

        $this->actingAs($admin)->post(route('view.staff'));

        $this->get(route('users-roles'))->assertForbidden();
        $this->get(route('dashboard'))->assertRedirect(route('my-bio-data'));
    }

    public function test_switching_back_restores_the_administrator_view(): void
    {
        $admin = $this->account('Administrator');

        $this->actingAs($admin)->post(route('view.staff'));
        $this->post(route('view.admin'))->assertRedirect(route('dashboard'));

        $this->get(route('users-roles'))->assertOk();
        $this->assertSame('Administrator', $admin->fresh()->role);
    }

    public function test_the_role_actually_held_never_changes(): void
    {
        $admin = $this->account('Administrator');

        $this->actingAs($admin)->post(route('view.staff'));

        $this->assertSame('Administrator', $admin->fresh()->actual_role);
        $this->assertTrue((bool) $admin->fresh()->is_admin);
    }

    public function test_a_non_administrator_cannot_switch(): void
    {
        $this->actingAs($this->account('Registry Officer'))
            ->post(route('view.staff'))->assertForbidden();
    }

    public function test_only_the_signed_in_account_is_switched(): void
    {
        $admin = $this->account('Administrator');
        $other = $this->account('Administrator');

        $this->actingAs($admin)->post(route('view.staff'));

        $this->assertSame('Administrator', $other->fresh()->role);
    }
}
