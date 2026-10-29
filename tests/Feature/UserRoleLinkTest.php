<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * An account's role is a reference to the roles table, and is_admin follows
 * from it.
 */
class UserRoleLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    public function test_the_account_holds_a_reference_rather_than_a_name(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'role_id'));
        $this->assertFalse(Schema::hasColumn('users', 'role'),
            'the name was superseded by the reference');
    }

    public function test_an_account_points_at_its_role(): void
    {
        $user = User::factory()->create(['role' => 'HOD EPM']);

        $this->assertTrue($user->roleRecord->is(Role::where('role', 'HOD EPM')->first()));
        $this->assertSame(Role::where('role', 'HOD EPM')->value('id'), $user->role_id);
    }

    public function test_the_role_still_reads_as_its_name(): void
    {
        $this->assertSame('HOD VEP', User::factory()->create(['role' => 'HOD VEP'])->role);
    }

    public function test_a_role_is_accepted_by_name_and_stored_as_a_reference(): void
    {
        $user = User::factory()->create(['role' => 'Commissioner']);

        $this->assertSame(Role::where('role', 'Commissioner')->value('id'),
            $user->fresh()->getAttributes()['role_id']);
    }

    public function test_renaming_a_role_renames_it_on_every_account_holding_it(): void
    {
        $user = User::factory()->create(['role' => 'HOD GAP']);

        Role::where('role', 'HOD GAP')->update(['role' => 'HOD General Admin']);

        // One source of truth: no copy on the account to fall out of step.
        $this->assertSame('HOD General Admin', $user->fresh()->role);
    }

    public function test_a_role_knows_which_accounts_hold_it(): void
    {
        User::factory()->create(['role' => 'Registry Officer']);
        User::factory()->create(['role' => 'Registry Officer']);
        User::factory()->create(['role' => 'HOD ICT']);

        $this->assertSame(2, Role::where('role', 'Registry Officer')->first()->users()->count());
        $this->assertSame(1, Role::where('role', 'HOD ICT')->first()->users()->count());
    }

    public function test_an_unrecognised_role_leaves_the_account_without_one(): void
    {
        $user = User::factory()->create(['role' => 'Supreme Commander']);

        $this->assertNull($user->role_id);
        $this->assertNull($user->role);
    }

    /* ---------------------------------------------------------- is_admin */

    public function test_an_administrator_is_marked_as_one(): void
    {
        $this->assertSame(1, User::factory()->create(['role' => 'Administrator'])->is_admin);
    }

    public function test_every_other_role_is_not(): void
    {
        foreach (Role::ordered()->pluck('role') as $role) {
            if ($role === 'Administrator') {
                continue;
            }

            $this->assertSame(0, User::factory()->create(['role' => $role])->is_admin,
                "{$role} should not carry administrative rights");
        }
    }

    public function test_granting_administrator_marks_the_account(): void
    {
        $user = User::factory()->create(['role' => 'Registry Officer']);
        $this->assertSame(0, $user->is_admin);

        $user->update(['role' => 'Administrator']);

        $this->assertSame(1, $user->fresh()->is_admin);
    }

    public function test_withdrawing_administrator_withdraws_the_mark(): void
    {
        $user = User::factory()->create(['role' => 'Administrator']);
        $this->assertSame(1, $user->is_admin);

        $user->update(['role' => 'HOD EOPs']);

        $this->assertSame(0, $user->fresh()->is_admin, 'is_admin must not be left behind');
    }

    public function test_is_admin_cannot_be_set_against_the_role(): void
    {
        // It follows from the role, so setting it by hand does not stick.
        $user = User::factory()->create(['role' => 'User']);
        $user->is_admin = 1;
        $user->save();

        $this->assertSame(0, $user->fresh()->is_admin);
    }

    public function test_an_account_with_no_role_is_not_an_administrator(): void
    {
        $user = User::factory()->create(['role' => 'Administrator']);
        $user->update(['role' => null]);

        $this->assertSame(0, $user->fresh()->is_admin);
    }

    public function test_opening_an_account_as_administrator_marks_it(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'new.admin',
            'email' => 'new.admin@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'Administrator',
        ])->assertRedirect(route('users-roles'));

        $created = User::where('username', 'new.admin')->firstOrFail();

        $this->assertSame('Administrator', $created->role);
        $this->assertSame(1, $created->is_admin);
    }

    public function test_opening_an_account_as_a_head_of_department_does_not(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'hod.vep',
            'email' => 'hod.vep@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'HOD VEP',
        ])->assertRedirect(route('users-roles'));

        $this->assertSame(0, User::where('username', 'hod.vep')->first()->is_admin);
    }

    public function test_the_listing_marks_administrators(): void
    {
        User::factory()->create(['username' => 'the.admin', 'role' => 'Administrator']);
        User::factory()->create(['username' => 'the.clerk', 'role' => 'Registry Officer']);

        $this->actingAs($this->admin())
            ->get(route('users-roles'))
            ->assertOk()
            ->assertSee('admin');
    }

    public function test_deleting_a_role_leaves_the_account_standing(): void
    {
        $role = Role::create(['role' => 'HOD Procurement']);
        $user = User::factory()->create(['role' => 'HOD Procurement']);

        $role->delete();

        // The account survives without a role rather than disappearing with it.
        $this->assertNotNull($user->fresh());
        $this->assertNull($user->fresh()->role_id);
        $this->assertSame(0, $user->fresh()->is_admin);
    }
}
