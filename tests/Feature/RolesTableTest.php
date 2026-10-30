<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolesTableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    public function test_the_table_has_the_columns_it_was_asked_for(): void
    {
        $this->assertSame(
            ['id', 'role_ulid', 'role', 'created_by', 'created_at'],
            Schema::getColumnListing('roles')
        );
    }

    public function test_a_role_is_not_given_an_updated_at(): void
    {
        // Named once and never edited, so there is nothing to track.
        $this->assertFalse(Schema::hasColumn('roles', 'updated_at'));
        $this->assertNull(Role::UPDATED_AT);
    }

    public function test_every_role_ships_with_the_schema(): void
    {
        $this->assertSame([
            'Commissioner',
            'Administrative Secretary',
            'Administrator',
            'Registry Officer',
            'User',
            'HOD GAP',
            // Finance & Account is its own directorate, not part of GAP.
            'HOD Finance & Account',
            'HOD ICT',
            'HOD EOPs',
            'HOD EPM',
            'HOD VEP',
        ], Role::ordered()->pluck('role')->all());
    }

    public function test_the_two_senior_roles_read_before_administrator(): void
    {
        $order = Role::ordered()->pluck('role')->all();

        $this->assertLessThan(array_search('Administrator', $order),
            array_search('Commissioner', $order));
        $this->assertLessThan(array_search('Administrator', $order),
            array_search('Administrative Secretary', $order));
    }

    public function test_the_order_does_not_follow_the_order_they_were_added(): void
    {
        // On a database that already had the first eight, these two take the
        // last two ids — and must still read first.
        Role::where('role', 'Commissioner')->update(['id' => 99]);

        $this->assertSame('Commissioner', Role::ordered()->first()->role);
    }

    public function test_a_role_added_later_sorts_to_the_end(): void
    {
        Role::create(['role' => 'HOD Procurement']);

        $this->assertSame('HOD Procurement', Role::ordered()->last()->role);
    }

    public function test_the_heads_of_department_are_there(): void
    {
        foreach (['HOD GAP', 'HOD Finance & Account', 'HOD ICT',
                  'HOD EOPs', 'HOD EPM', 'HOD VEP'] as $role) {
            $this->assertTrue(Role::where('role', $role)->exists(), "{$role} is missing");
        }
    }

    public function test_each_role_is_given_a_ulid_and_a_created_at(): void
    {
        foreach (Role::all() as $role) {
            $this->assertNotNull($role->role_ulid);
            $this->assertSame(26, strlen($role->role_ulid));
            $this->assertNotNull($role->created_at);
        }

        $this->assertSame(Role::count(), Role::distinct()->count('role_ulid'));
    }

    public function test_a_role_the_system_ships_with_has_no_creator(): void
    {
        $this->assertSame(0, Role::whereNotNull('created_by')->count());
        $this->assertNull(Role::first()->creator);
    }

    public function test_a_role_added_later_records_who_added_it(): void
    {
        $admin = $this->admin();

        $role = Role::create(['role' => 'HOD Legal', 'created_by' => $admin->id]);

        $this->assertTrue($role->fresh()->creator->is($admin));
    }

    public function test_the_same_role_cannot_be_added_twice(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Role::create(['role' => 'Administrator']);
    }

    public function test_reseeding_leaves_the_roles_as_they_were(): void
    {
        $before = Role::orderBy('id')->pluck('role_ulid', 'role');

        (new \Database\Seeders\RoleSeeder)->run();

        $this->assertCount(count(Role::SHIPPED), Role::all(), 're-running must not duplicate a role');
        $this->assertEquals($before, Role::orderBy('id')->pluck('role_ulid', 'role'),
            'the ulid a role is known by must not change');
    }

    public function test_the_role_dropdown_is_drawn_from_the_table(): void
    {
        $response = $this->actingAs($this->admin())->get(route('users-roles'));

        $response->assertOk();

        $this->assertSame(Role::ordered()->pluck('role')->all(),
            array_keys($response->viewData('roles')));

        foreach (['Commissioner', 'Administrative Secretary', 'HOD GAP', 'HOD ICT',
                  'HOD EOPs', 'HOD EPM', 'HOD VEP'] as $role) {
            $response->assertSee($role);
        }
    }

    public function test_a_role_added_to_the_table_shows_up_in_the_dropdown(): void
    {
        Role::create(['role' => 'HOD Procurement']);

        $this->actingAs($this->admin())
            ->get(route('users-roles'))
            ->assertSee('HOD Procurement');
    }

    public function test_an_account_can_be_opened_with_a_head_of_department_role(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'hod.ict',
            'email' => 'hod.ict@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'HOD ICT',
        ])->assertRedirect(route('users-roles'));

        // Read through the model: the account stores role_id, not the name.
        $this->assertSame('HOD ICT', User::where('username', 'hod.ict')->first()->role);
    }

    public function test_a_role_that_is_not_in_the_table_is_refused(): void
    {
        $staff = BasicInformation::factory()->create();

        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'invented',
            'email' => 'invented@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'Supreme Commander',
        ])->assertSessionHasErrors('role');

        $this->assertNull(User::where('username', 'invented')->first());
    }

    public function test_the_old_slug_form_of_a_role_is_no_longer_accepted(): void
    {
        $staff = BasicInformation::factory()->create();

        // Accounts were migrated off 'registry-officer' onto 'Registry Officer'.
        $this->actingAs($this->admin())->post(route('users-roles.store'), [
            'basic_information_ulid' => $staff->ulid,
            'username' => 'slugged',
            'email' => 'slugged@inec.gov.ng',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'registry-officer',
        ])->assertSessionHasErrors('role');
    }

    public function test_the_roles_card_lists_them_with_their_reference(): void
    {
        $response = $this->actingAs($this->admin())->get(route('users-roles'));

        $response->assertSee('HOD EOPs');
        $response->assertSee(Role::where('role', 'HOD EOPs')->value('role_ulid'));
        // No creator, so the card says so rather than leaving it blank.
        $response->assertSee('System');
    }

    public function test_a_role_knows_which_accounts_hold_it(): void
    {
        $this->admin();

        $this->assertSame(1, Role::where('role', 'Administrator')->first()->users()->count());
        $this->assertSame(0, Role::where('role', 'HOD VEP')->first()->users()->count());
    }
}
