<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermissionsTableTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name = 'HOD ICT'): Role
    {
        return Role::where('role', $name)->firstOrFail();
    }

    public function test_the_table_has_the_columns_it_was_asked_for(): void
    {
        $this->assertSame(
            ['id', 'role_ulid', 'permission', 'created_at'],
            Schema::getColumnListing('permissions')
        );
    }

    public function test_a_permission_is_not_given_an_updated_at(): void
    {
        // Granted or withdrawn, never edited.
        $this->assertFalse(Schema::hasColumn('permissions', 'updated_at'));
        $this->assertNull(Permission::UPDATED_AT);
    }

    public function test_a_permission_belongs_to_a_role(): void
    {
        $role = $this->role();

        $permission = Permission::create(['role_ulid' => $role->role_ulid, 'permission' => 'read']);

        $this->assertTrue($permission->role->is($role));
        $this->assertNotNull($permission->created_at);
    }

    public function test_a_role_can_hold_several_permissions(): void
    {
        $role = $this->role();

        $role->grant(['create', 'read', 'update', 'delete']);

        $this->assertSame(['create', 'delete', 'read', 'update'], $role->fresh()->permissionNames());
        $this->assertSame(4, $role->permissions()->count());
    }

    public function test_the_same_permission_cannot_be_granted_to_a_role_twice(): void
    {
        $role = $this->role();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Permission::create(['role_ulid' => $role->role_ulid, 'permission' => 'read']);
        Permission::create(['role_ulid' => $role->role_ulid, 'permission' => 'read']);
    }

    public function test_granting_again_what_a_role_already_holds_changes_nothing(): void
    {
        $role = $this->role();

        $role->grant('read');
        $role->grant('read');

        $this->assertSame(1, $role->permissions()->count());
    }

    public function test_the_same_permission_can_be_granted_to_different_roles(): void
    {
        $before = Permission::where('permission', 'read')->count();

        $this->role('HOD ICT')->grant('read');
        $this->role('HOD VEP')->grant('read');

        $this->assertSame($before + 2, Permission::where('permission', 'read')->count());
    }

    public function test_a_permission_can_be_withdrawn(): void
    {
        $role = $this->role();
        $role->grant(['read', 'update']);

        $role->revoke('update');

        $this->assertSame(['read'], $role->fresh()->permissionNames());
    }

    public function test_a_role_knows_what_it_may_do(): void
    {
        $role = $this->role();
        $role->grant('read');

        $this->assertTrue($role->grants('read'));
        $this->assertFalse($role->grants('delete'));
    }

    public function test_a_permission_cannot_be_granted_to_a_role_that_does_not_exist(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Permission::create([
            'role_ulid' => '01NOTAREALROLEULID00000000',
            'permission' => 'read',
        ]);
    }

    public function test_deleting_a_role_takes_its_permissions_with_it(): void
    {
        $role = Role::create(['role' => 'HOD Procurement']);
        $role->grant(['read', 'update']);

        $this->assertSame(2, Permission::where('role_ulid', $role->role_ulid)->count());

        $role->delete();

        $this->assertSame(0, Permission::where('role_ulid', $role->role_ulid)->count(),
            'a permission granted to a role that no longer exists is meaningless');
    }

    public function test_only_the_administrator_has_been_decided_on(): void
    {
        // Administrator ships configured; what the rest may do has not been
        // decided, so they hold nothing.
        $this->assertSame([], $this->role('HOD ICT')->permissionNames());
        $this->assertSame([], $this->role('Commissioner')->permissionNames());
        $this->assertNotSame([], $this->role('Administrator')->permissionNames());
    }

    public function test_the_roles_card_shows_what_each_role_may_do(): void
    {
        $this->role('HOD ICT')->grant(['read', 'update']);

        $response = $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->get(route('users-roles'));

        $response->assertOk();

        // Shown as the ticked boxes of the role's own grant form.
        $this->assertSame(['read', 'update'],
            $response->viewData('permissionsByRole')['HOD ICT']);
        $this->assertSame([], $response->viewData('permissionsByRole')['HOD VEP']);
    }

    public function test_a_permission_is_scoped_to_its_role(): void
    {
        $ict = $this->role('HOD ICT');
        $vep = $this->role('HOD VEP');

        $ict->grant('delete');

        $this->assertSame(['delete'], Permission::for($ict)->pluck('permission')->all());
        $this->assertSame([], Permission::for($vep)->pluck('permission')->all());
    }
}
