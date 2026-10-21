<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route the sidebar can reach. Kept here so a new module that forgets
     * its middleware fails this test rather than shipping open.
     */
    public static function protectedRoutes(): array
    {
        return array_map(fn ($url) => [$url], [
            '/dashboard',
            '/dashboard/profile-settings',
            '/dashboard/change-password',
            '/dashboard/registry/bio-data',
            '/dashboard/registry/nominal-roll',
            '/dashboard/registry/posting-deployment',
            '/dashboard/registry/confirmation',
            '/dashboard/registry/record-of-service',
            '/dashboard/registry/query-discipline',
            '/dashboard/pension-gratuity/retirement-exit',
            '/dashboard/registry/record-amendments',
            '/dashboard/staff/basic-info',
            '/dashboard/staff/leave-record',
            '/dashboard/finance-account/payroll',
            '/dashboard/finance-account/deductions',
            '/dashboard/human-resources/leave-management',
            '/dashboard/reports/payroll',
            '/dashboard/ict',
            '/dashboard/config',
            '/dashboard/config/users-roles',
            '/dashboard/docs/1',
            '/storage/some-document.pdf',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('protectedRoutes')]
    public function test_guests_are_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('protectedRoutes')]
    public function test_authenticated_users_are_not_bounced_to_login(string $url): void
    {
        $response = $this->actingAs($this->activeUser())->get($url);

        // Some pages legitimately redirect once signed in — the Add Staff Record
        // wizard sends you to its first step. What must never happen is a bounce
        // back to the login screen.
        $this->assertNotSame(
            route('login'),
            $response->headers->get('Location'),
            "$url sent an authenticated user back to login"
        );
    }

    public function test_login_screen_is_public(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_valid_credentials_sign_the_user_in(): void
    {
        $user = $this->activeUser();

        $this->post('/login', [
            'userhandle' => $user->username,
            'secrethash' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->activeUser();

        $this->from('/login')->post('/login', [
            'userhandle' => $user->username,
            'secrethash' => 'not-the-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $user = $this->activeUser(['username' => 'suspended', 'is_active' => 0]);

        $this->from('/login')->post('/login', [
            'userhandle' => $user->username,
            'secrethash' => 'password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_an_account_deactivated_mid_session_is_logged_out(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => 0]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guests_are_sent_back_to_where_they_were_headed(): void
    {
        $user = $this->activeUser();

        $this->get('/dashboard/pension-gratuity/retirement-exit')->assertRedirect('/login');

        $this->post('/login', [
            'userhandle' => $user->username,
            'secrethash' => 'password',
        ])->assertRedirect('/dashboard/pension-gratuity/retirement-exit');
    }

    public function test_signed_in_users_are_kept_off_the_login_screen(): void
    {
        $this->actingAs($this->activeUser())->get('/login')->assertRedirect('/dashboard');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->activeUser())->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    private function activeUser(array $overrides = []): User
    {
        $user = User::firstOrNew(['username' => $overrides['username'] ?? 'tester']);

        $user->fill(array_merge([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => $user->basic_information_ulid ?: BasicInformation::factory()
                ->create(['surname' => 'Test', 'firstname' => 'Account', 'othernames' => ''])
                ->ulid,
            'email' => ($overrides['username'] ?? 'tester') . '@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'administrator',
            'is_active' => 1,
        ], $overrides));

        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }
}
