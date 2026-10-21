<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A dashboard account belongs to an officer on the staff register. The account
 * holds credentials and permissions; every personal detail is read from the
 * staff record.
 */
class UserStaffLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }

    public function test_personal_columns_no_longer_live_on_the_users_table(): void
    {
        foreach (['surname', 'firstname', 'othernames', 'ministry', 'department'] as $column) {
            $this->assertFalse(
                Schema::hasColumn('users', $column),
                "users.{$column} should have moved to basic_informations"
            );
        }

        $this->assertTrue(Schema::hasColumn('users', 'basic_information_ulid'));
    }

    public function test_the_account_keeps_what_is_about_the_account(): void
    {
        foreach (['username', 'email', 'password', 'role', 'permissions', 'is_active'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "users.{$column} is missing");
        }
    }

    public function test_an_account_reads_its_name_from_the_staff_record(): void
    {
        $staff = BasicInformation::factory()->create([
            'surname' => 'Okon', 'firstname' => 'Grace', 'othernames' => 'Eno',
            'department' => 'Administration & Finance',
        ]);

        $user = User::factory()->create(['basic_information_ulid' => $staff->ulid]);

        $this->assertSame('Okon', $user->surname);
        $this->assertSame('Grace', $user->firstname);
        $this->assertSame('Eno', $user->othernames);
        $this->assertSame('Administration & Finance', $user->department);
        $this->assertSame('Okon, Grace Eno', $user->full_name);
    }

    public function test_renaming_the_officer_renames_the_account_holder(): void
    {
        $staff = BasicInformation::factory()->create(['surname' => 'Before']);
        $user = User::factory()->create(['basic_information_ulid' => $staff->ulid]);

        $staff->update(['surname' => 'After']);

        // One source of truth: no copy on the account to fall out of step.
        $this->assertSame('After', $user->fresh()->surname);
    }

    public function test_an_account_points_at_its_officer(): void
    {
        $staff = BasicInformation::factory()->create();
        $user = User::factory()->create(['basic_information_ulid' => $staff->ulid]);

        $this->assertTrue($user->staff->is($staff));
    }

    public function test_an_account_is_opened_for_an_officer_on_the_register(): void
    {
        $staff = BasicInformation::factory()->create(['surname' => 'Edet', 'firstname' => 'Mfon']);

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'm.edet',
            'email' => 'm.edet@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'registry-officer',
            'permissions' => ['read', 'update'],
            'is_active' => 1,
        ])->assertRedirect(route('users-roles'));

        $created = User::where('username', 'm.edet')->firstOrFail();

        $this->assertSame($staff->ulid, $created->basic_information_ulid);
        $this->assertSame('Edet', $created->surname);
        $this->assertSame(['read', 'update'], json_decode($created->permissions, true));
        $this->assertNotSame('secret-password', $created->password, 'the password must be hashed');
    }

    public function test_an_account_cannot_be_opened_for_someone_off_the_register(): void
    {
        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => 'NOTAREALULID00000000000000',
            'username' => 'ghost',
            'email' => 'ghost@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'user',
        ])->assertSessionHasErrors('basic_information_ulid');

        $this->assertNull(User::where('username', 'ghost')->first());
    }

    public function test_an_officer_may_only_hold_one_account(): void
    {
        $staff = BasicInformation::factory()->create();
        User::factory()->create(['basic_information_ulid' => $staff->ulid]);

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'second-account',
            'email' => 'second@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'user',
        ])->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(1, User::where('basic_information_ulid', $staff->ulid)->count());
    }

    public function test_an_officer_must_be_chosen(): void
    {
        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'username' => 'no-officer',
            'email' => 'no-officer@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'user',
        ])->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_a_mismatched_password_confirmation_is_refused(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'mistyped',
            'email' => 'mistyped@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'something-else',
            'role' => 'user',
        ])->assertSessionHasErrors('password');
    }

    public function test_the_listing_shows_each_holder_and_their_department(): void
    {
        $staff = BasicInformation::factory()->create([
            'surname' => 'Listed', 'firstname' => 'Officer', 'department' => 'Legal & Litigation',
        ]);
        User::factory()->create(['basic_information_ulid' => $staff->ulid, 'username' => 'listed-user']);

        $response = $this->actingAs($this->admin())->get(route('users-roles'));

        $response->assertOk();
        $response->assertSee('listed-user');
        $response->assertSee('Listed');
        $response->assertSee('Legal &amp; Litigation', false);
        $response->assertSee($staff->staff_number);
    }

    public function test_an_officer_with_an_account_is_not_offered_again(): void
    {
        $taken = BasicInformation::factory()->create(['surname' => 'Taken']);
        $free = BasicInformation::factory()->create(['surname' => 'Free']);
        User::factory()->create(['basic_information_ulid' => $taken->ulid]);

        $options = $this->actingAs($this->admin())->get(route('users-roles'))->viewData('staffOptions');

        $this->assertArrayHasKey($free->ulid, $options);
        $this->assertArrayNotHasKey($taken->ulid, $options);
    }

    public function test_an_account_can_be_withdrawn_and_restored(): void
    {
        $user = User::factory()->create(['is_active' => 1]);

        $this->actingAs($this->admin())->post(route('users-roles.toggle', $user));
        $this->assertSame(0, $user->fresh()->is_active);

        $this->actingAs($this->admin())->post(route('users-roles.toggle', $user));
        $this->assertSame(1, $user->fresh()->is_active);
    }

    public function test_the_navbar_greets_the_officer_by_name(): void
    {
        $staff = BasicInformation::factory()->create(['firstname' => 'Idongesit']);
        $user = User::factory()->create(['basic_information_ulid' => $staff->ulid]);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('Welcome, Idongesit!');
    }
}
