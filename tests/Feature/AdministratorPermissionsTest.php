<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The first role decided on: an administrator may create, read, modify,
 * delete and back up data.
 */
class AdministratorPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): Role
    {
        return Role::where('role', 'Administrator')->firstOrFail();
    }

    public function test_backing_up_data_is_a_permission_that_can_be_granted(): void
    {
        $this->assertArrayHasKey('backup-data', Permission::AVAILABLE);
        $this->assertSame('Backup Data', Permission::label('backup-data'));
    }

    public function test_update_reads_as_modify_or_update(): void
    {
        $this->assertSame('Modify / Update', Permission::label('update'));
    }

    public function test_an_administrator_may_do_all_five(): void
    {
        $this->assertSame(
            ['backup-data', 'create', 'delete', 'read', 'update'],
            $this->administrator()->permissionNames()
        );
    }

    public function test_an_administrator_may_create_read_modify_delete_and_back_up(): void
    {
        $role = $this->administrator();

        foreach (['create', 'read', 'update', 'delete', 'backup-data'] as $permission) {
            $this->assertTrue($role->grants($permission), "an administrator should be able to {$permission}");
        }
    }

    public function test_no_other_role_has_been_decided_on_yet(): void
    {
        $decided = Role::ordered()
            ->filter(fn ($role) => $role->permissionNames() !== [])
            ->pluck('role');

        $this->assertSame(['Administrator'], $decided->all());
    }

    public function test_the_shipped_configuration_says_what_an_administrator_may_do(): void
    {
        $this->assertSame(
            ['create', 'read', 'update', 'delete', 'backup-data'],
            \Database\Seeders\PermissionSeeder::GRANTS['Administrator']
        );
    }

    public function test_reseeding_leaves_the_grants_as_they_were(): void
    {
        $before = $this->administrator()->permissionNames();

        (new \Database\Seeders\PermissionSeeder)->run();

        $this->assertSame($before, $this->administrator()->fresh()->permissionNames());
        $this->assertSame(5, $this->administrator()->permissions()->count(),
            're-running must not grant the same permission twice');
    }

    public function test_an_account_can_be_opened_with_all_of_them(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->post(route('users-roles.store'), [
                'basic_information_ulid' => $staff->ulid,
                'username' => 'second.admin',
                'email' => 'second.admin@inec.gov.ng',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => 'Administrator',
                'permissions' => ['create', 'read', 'update', 'delete', 'backup-data'],
            ])->assertRedirect(route('users-roles'));

        $this->assertSame(
            ['create', 'read', 'update', 'delete', 'backup-data'],
            json_decode(User::where('username', 'second.admin')->value('permissions'), true)
        );
    }

    public function test_an_administrator_account_may_still_be_given_less(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->post(route('users-roles.store'), [
                'basic_information_ulid' => $staff->ulid,
                'username' => 'read.only.admin',
                'email' => 'read.only@inec.gov.ng',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => 'Administrator',
                'permissions' => ['read'],
            ])->assertRedirect(route('users-roles'));

        $this->assertSame(['read'],
            json_decode(User::where('username', 'read.only.admin')->value('permissions'), true));
    }

    public function test_backing_up_data_is_not_open_to_a_role_that_was_not_granted_it(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->post(route('users-roles.store'), [
                'basic_information_ulid' => $staff->ulid,
                'username' => 'hod.backup',
                'email' => 'hod.backup@inec.gov.ng',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => 'HOD ICT',
                'permissions' => ['backup-data'],
            ])->assertSessionHasErrors('permissions.0');
    }

    public function test_the_form_offers_an_administrator_all_five(): void
    {
        $byRole = $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->get(route('users-roles'))
            ->viewData('permissionsByRole');

        $this->assertSame(
            ['backup-data', 'create', 'delete', 'read', 'update'],
            $byRole['Administrator']
        );
    }
}
