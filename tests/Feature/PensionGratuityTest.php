<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PensionGratuityTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'pension-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Pension', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'pension@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'Administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    private function officer(string $surname, string $dateOfBirth): BasicInformation
    {
        $state = State::where('name', 'Cross River')->firstOrFail();

        return BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'file_number' => 'PEN/F/' . Str::random(6),
            'surname' => $surname, 'firstname' => 'Officer', 'othernames' => '',
            'date_of_birth' => $dateOfBirth, 'phone_number' => '08030000000',
            'state_of_origin' => $state->id, 'lga_of_origin' => $state->lgas()->value('id'),
        ]);
    }

    public function test_the_section_serves_its_three_pages(): void
    {
        $user = $this->actor();

        foreach (['retirement-exit', 'cps', 'deceased'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_every_page_sits_under_the_pension_gratuity_prefix(): void
    {
        $this->assertStringContainsString('/dashboard/pension-gratuity/retirement-exit', route('retirement-exit'));
        $this->assertStringContainsString('/dashboard/pension-gratuity/cps', route('cps'));
        $this->assertStringContainsString('/dashboard/pension-gratuity/deceased', route('deceased'));
    }

    public function test_retirement_and_exit_no_longer_answers_under_registry(): void
    {
        $this->actingAs($this->actor())
            ->get('/dashboard/registry/retirement-exit')
            ->assertNotFound();
    }

    public function test_the_pages_are_handled_by_the_pension_controller(): void
    {
        foreach (['retirement-exit', 'cps', 'deceased'] as $name) {
            $action = app('router')->getRoutes()->getByName($name)->getActionName();

            $this->assertStringContainsString('PensionGratuityController', $action);
        }
    }

    public function test_the_projection_lists_officers_approaching_sixty(): void
    {
        // Turns 60 inside the five-year window the page covers.
        $soon = $this->officer('Approaching', now()->subYears(58)->toDateString());
        // Only 40: far outside it.
        $this->officer('Youngest', now()->subYears(40)->toDateString());

        $response = $this->actingAs($this->actor())->get(route('retirement-exit'));
        $response->assertOk();

        // Checked against the projection itself: every officer also appears in
        // the exit form's staff picker, so the rendered page is no guide.
        $projected = collect($response->viewData('projection'))->map(fn ($row) => $row->staff->surname);

        $this->assertTrue($projected->contains('Approaching'));
        $this->assertFalse($projected->contains('Youngest'));
        $response->assertSee($soon->file_number);
    }

    public function test_an_exit_is_recorded_through_the_new_controller(): void
    {
        $officer = $this->officer('Departing', '1964-12-05');

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'retirement',
            'effective_date' => '2024-12-05',
            'gratuity_status' => 'paid',
            'pension_status' => 'processing',
            'mode_of_retirement' => 'age',
        ])->assertRedirect(route('retirement-exit'));

        $this->assertDatabaseHas('staff_exits', [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'retirement',
            'gratuity_status' => 'paid',
        ]);
    }

    public function test_an_unknown_exit_type_is_refused(): void
    {
        $officer = $this->officer('Rejected', '1970-01-01');

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'abducted-by-aliens',
            'effective_date' => '2024-12-05',
            'gratuity_status' => 'paid',
            'pension_status' => 'paid',
        ])->assertSessionHasErrors('exit_type');

        $this->assertSame(0, StaffExit::count());
    }
}
