<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A role says what may be granted; an account is given some of that, never
 * more.
 */
class RolePermissionGrantTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function role(string $name = 'HOD ICT'): Role
    {
        return Role::where('role', $name)->firstOrFail();
    }

    private function openAccount(array $overrides = [])
    {
        return $this->actingAs($this->admin())->post(route('users-roles.store'), array_merge([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'username' => 'new.account',
            'email' => 'new.account@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'HOD ICT',
        ], $overrides));
    }

    private function heldBy(string $username): array
    {
        return json_decode(User::where('username', $username)->firstOrFail()->permissions, true) ?: [];
    }

    /* ------------------------------------------------ granting to a role */

    public function test_a_role_is_granted_the_permissions_that_were_ticked(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), ['permissions' => ['read', 'update']])
            ->assertRedirect(route('users-roles'));

        $this->assertSame(['read', 'update'], $this->role()->fresh()->permissionNames());
    }

    public function test_clearing_a_box_withdraws_that_permission(): void
    {
        $this->role()->grant(['create', 'read', 'update', 'delete']);

        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), ['permissions' => ['read']]);

        $this->assertSame(['read'], $this->role()->fresh()->permissionNames());
    }

    public function test_submitting_nothing_leaves_the_role_able_to_do_nothing(): void
    {
        $this->role()->grant(['read', 'update']);

        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), []);

        $this->assertSame([], $this->role()->fresh()->permissionNames());
    }

    public function test_a_permission_outside_the_vocabulary_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), ['permissions' => ['detonate']])
            ->assertSessionHasErrors('permissions.0');

        $this->assertSame([], $this->role()->fresh()->permissionNames());
    }

    public function test_granting_a_role_does_not_touch_another(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role('HOD ICT')), ['permissions' => ['delete']]);

        $this->assertSame(['delete'], $this->role('HOD ICT')->fresh()->permissionNames());
        $this->assertSame([], $this->role('HOD VEP')->fresh()->permissionNames());
    }

    /* ------------------------------------- what an account may be given */

    public function test_an_account_can_be_given_what_its_role_allows(): void
    {
        $this->role()->grant(['read', 'update']);

        $this->openAccount(['permissions' => ['read']])->assertRedirect(route('users-roles'));

        $this->assertSame(['read'], $this->heldBy('new.account'));
    }

    public function test_an_account_need_not_be_given_everything_its_role_allows(): void
    {
        $this->role()->grant(['create', 'read', 'update', 'delete']);

        $this->openAccount(['permissions' => ['read']]);

        // The role allows four; this officer was given one.
        $this->assertSame(['read'], $this->heldBy('new.account'));
        $this->assertCount(4, $this->role()->fresh()->permissionNames());
    }

    public function test_an_account_cannot_be_given_what_its_role_does_not_allow(): void
    {
        $this->role()->grant(['read']);

        $this->openAccount(['permissions' => ['read', 'delete']])
            ->assertSessionHasErrors('permissions.1');

        $this->assertNull(User::where('username', 'new.account')->first());
    }

    public function test_an_account_under_a_role_that_allows_nothing_gets_nothing(): void
    {
        $this->openAccount(['permissions' => ['read']])->assertSessionHasErrors('permissions.0');
    }

    public function test_an_account_can_be_opened_with_no_permissions_at_all(): void
    {
        $this->role()->grant(['read']);

        $this->openAccount()->assertRedirect(route('users-roles'));

        $this->assertSame([], $this->heldBy('new.account'));
    }

    public function test_the_same_permission_under_a_different_role_is_judged_separately(): void
    {
        $this->role('HOD ICT')->grant(['delete']);

        // HOD VEP allows nothing, so delete is not open to it.
        $this->openAccount(['role' => 'HOD VEP', 'permissions' => ['delete']])
            ->assertSessionHasErrors('permissions.0');
    }

    /* ------------------------------------- withdrawing from a role later */

    public function test_withdrawing_from_a_role_withdraws_it_from_its_accounts(): void
    {
        $this->role()->grant(['read', 'delete']);
        $this->openAccount(['permissions' => ['read', 'delete']]);

        $this->assertSame(['read', 'delete'], $this->heldBy('new.account'));

        // The role may no longer delete, so neither may the account.
        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), ['permissions' => ['read']]);

        $this->assertSame(['read'], $this->heldBy('new.account'));
    }

    public function test_withdrawing_leaves_alone_what_the_role_still_allows(): void
    {
        $this->role()->grant(['create', 'read', 'delete']);
        $this->openAccount(['permissions' => ['create', 'read']]);

        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role()), ['permissions' => ['create', 'read']]);

        $this->assertSame(['create', 'read'], $this->heldBy('new.account'));
    }

    public function test_withdrawing_does_not_reach_accounts_under_another_role(): void
    {
        $this->role('HOD ICT')->grant(['read']);
        $this->role('HOD VEP')->grant(['read']);

        $this->openAccount(['username' => 'ict.user', 'email' => 'ict@inec.gov.ng',
                            'role' => 'HOD ICT', 'permissions' => ['read']]);
        $this->openAccount(['username' => 'vep.user', 'email' => 'vep@inec.gov.ng',
                            'role' => 'HOD VEP', 'permissions' => ['read']]);

        $this->actingAs($this->admin())
            ->post(route('users-roles.permissions', $this->role('HOD ICT')), []);

        $this->assertSame([], $this->heldBy('ict.user'));
        $this->assertSame(['read'], $this->heldBy('vep.user'));
    }

    /* ----------------------------------------------------------- the page */

    public function test_the_page_carries_what_each_role_allows(): void
    {
        $this->role('HOD ICT')->grant(['read', 'update']);

        $byRole = $this->actingAs($this->admin())->get(route('users-roles'))
            ->viewData('permissionsByRole');

        $this->assertSame(['read', 'update'], $byRole['HOD ICT']);
        $this->assertSame([], $byRole['HOD VEP']);
    }

    public function test_the_page_offers_every_role_a_way_to_be_granted(): void
    {
        $response = $this->actingAs($this->admin())->get(route('users-roles'));

        foreach (Role::all() as $role) {
            $response->assertSee(route('users-roles.permissions', $role), false);
        }
    }
}
