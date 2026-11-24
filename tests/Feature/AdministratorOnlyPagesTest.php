<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings, Users, Roles & Permissions, Notification Templates and Back Up &
 * Data are the Administrator's alone — whoever else holds an office.
 */
class AdministratorOnlyPagesTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['config', 'users-roles', 'notification-templates', 'backup-data'];

    private const LABELS = ['Settings', 'Users, Roles & Permissions', 'Notification Templates', 'Backup & Data'];

    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    public static function everyoneButTheAdministrator(): array
    {
        return [
            'HOD GAP' => ['HOD GAP'],
            'a Registry Officer' => ['Registry Officer'],
            'the head of another department' => ['HOD EOPs'],
            'the Commissioner' => ['Commissioner'],
            'the Administrative Secretary' => ['Administrative Secretary'],
        ];
    }

    #[DataProvider('everyoneButTheAdministrator')]
    public function test_they_are_refused_each_page(string $role): void
    {
        $viewer = $this->account($role);

        foreach (self::PAGES as $page) {
            $this->actingAs($viewer)->get(route($page))->assertForbidden();
        }
    }

    #[DataProvider('everyoneButTheAdministrator')]
    public function test_they_are_refused_what_those_pages_save(string $role): void
    {
        $viewer = $this->account($role);

        foreach (['config.organisation', 'config.rules', 'users-roles.store'] as $route) {
            $this->actingAs($viewer)->post(route($route))->assertForbidden();
        }
    }

    #[DataProvider('everyoneButTheAdministrator')]
    public function test_they_are_not_offered_them_on_the_sidebar(string $role): void
    {
        $labels = collect(Menu::forUser($this->account($role)))
            ->flatMap(fn ($group) => array_merge([$group['label']], array_column($group['children'] ?? [], 'label')))
            ->all();

        foreach (self::LABELS as $label) {
            $this->assertNotContains($label, $labels);
        }
    }

    public function test_an_electoral_officer_is_refused_too(): void
    {
        $eo = $this->account('Registry Officer', ['designation' => 'Electoral Officer']);

        foreach (self::PAGES as $page) {
            $this->actingAs($eo)->get(route($page))->assertForbidden();
        }
    }

    public function test_the_administrator_keeps_all_four(): void
    {
        $admin = $this->account('Administrator');

        foreach (self::PAGES as $page) {
            $this->actingAs($admin)->get(route($page))->assertOk();
        }

        $labels = collect(Menu::forUser($admin))
            ->flatMap(fn ($group) => array_column($group['children'] ?? [], 'label'))->all();

        foreach (self::LABELS as $label) {
            $this->assertContains($label, $labels);
        }
    }

    public function test_the_rest_of_administration_is_left_as_it_was(): void
    {
        $viewer = $this->account('Registry Officer');

        $labels = collect(Menu::forUser($viewer))
            ->flatMap(fn ($group) => array_column($group['children'] ?? [], 'label'))->all();

        $this->assertContains('Departments & Units', $labels);
        $this->assertContains('Audit Trail', $labels);
        $this->assertContains('Change Password', $labels);
    }

    public function test_the_sign_in_redirect_does_not_follow_them_to_a_refused_page(): void
    {
        $this->assertFalse(Menu::allows($this->account('HOD GAP'), route('backup-data')));
        $this->assertTrue(Menu::allows($this->account('Administrator'), route('backup-data')));
    }
}
