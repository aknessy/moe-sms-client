<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeceasedModuleTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes);
    }

    public function test_the_staff_record_carries_both_status_flags(): void
    {
        $this->assertTrue(Schema::hasColumn('basic_informations', 'is_deceased'));
        $this->assertTrue(Schema::hasColumn('basic_informations', 'is_retired'));
    }

    public function test_an_officer_is_neither_deceased_nor_retired_by_default(): void
    {
        // Read back from the database: the default is applied by the column.
        $officer = $this->officer()->fresh();

        $this->assertSame(0, $officer->is_deceased);
        $this->assertSame(0, $officer->is_retired);
    }

    public function test_an_officer_can_be_recorded_as_deceased(): void
    {
        $officer = $this->officer(['surname' => 'Nwachukwu', 'firstname' => 'Chidi']);

        $this->actingAs($this->actor())->post(route('deceased.store'), [
            'basic_information_ulid' => $officer->ulid,
            'is_deceased' => 1,
        ])->assertRedirect(route('deceased'));

        $this->assertSame(1, $officer->fresh()->is_deceased);
    }

    public function test_a_record_made_in_error_can_be_reversed(): void
    {
        $officer = $this->officer(['is_deceased' => 1]);

        $this->actingAs($this->actor())->post(route('deceased.store'), [
            'basic_information_ulid' => $officer->ulid,
            'is_deceased' => 0,
        ])->assertRedirect(route('deceased'));

        $this->assertSame(0, $officer->fresh()->is_deceased);
    }

    public function test_an_officer_must_be_chosen(): void
    {
        $this->actingAs($this->actor())
            ->post(route('deceased.store'), ['is_deceased' => 1])
            ->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_an_officer_off_the_register_is_refused(): void
    {
        $this->actingAs($this->actor())->post(route('deceased.store'), [
            'basic_information_ulid' => 'NOTAREALULID00000000000000',
            'is_deceased' => 1,
        ])->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_the_listing_shows_only_deceased_officers(): void
    {
        $this->officer(['surname' => 'Departed', 'is_deceased' => 1]);
        $this->officer(['surname' => 'Serving', 'is_deceased' => 0]);

        $listed = $this->actingAs($this->actor())->get(route('deceased'))
            ->viewData('deceased')
            ->getCollection()
            ->map(fn ($p) => $p->surname);

        $this->assertTrue($listed->contains('Departed'));
        $this->assertFalse($listed->contains('Serving'));
    }

    public function test_the_listing_carries_the_date_and_benefit_status_from_the_exit(): void
    {
        $officer = $this->officer(['surname' => 'Logged']);

        StaffExit::create([
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'death',
            'effective_date' => '2025-09-03',
            'gratuity_status' => 'processing',
            'pension_status' => 'pending',
        ]);

        $response = $this->actingAs($this->actor())->get(route('deceased'));

        $response->assertOk();
        $response->assertSee('2025-09-03');
        $response->assertSee('Processing');
    }

    public function test_recording_a_death_exit_marks_the_officer_deceased(): void
    {
        $officer = $this->officer();

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'death',
            'effective_date' => '2025-09-03',
            'gratuity_status' => 'pending',
            'pension_status' => 'pending',
        ])->assertRedirect(route('retirement-exit'));

        $this->assertSame(1, $officer->fresh()->is_deceased);
        $this->assertSame(0, $officer->fresh()->is_retired);
    }

    public function test_recording_a_retirement_exit_marks_the_officer_retired(): void
    {
        $officer = $this->officer();

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'retirement',
            'effective_date' => '2025-12-05',
            'gratuity_status' => 'paid',
            'pension_status' => 'paid',
        ])->assertRedirect(route('retirement-exit'));

        $this->assertSame(1, $officer->fresh()->is_retired);
        $this->assertSame(0, $officer->fresh()->is_deceased);
    }

    public function test_an_exit_that_is_neither_sets_no_flag(): void
    {
        $officer = $this->officer();

        $this->actingAs($this->actor())->post(route('retirement-exit.store'), [
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'resignation',
            'effective_date' => '2025-02-28',
            'gratuity_status' => 'pending',
            'pension_status' => 'pending',
        ]);

        $this->assertSame(0, $officer->fresh()->is_deceased);
        $this->assertSame(0, $officer->fresh()->is_retired);
    }

    public function test_an_exit_written_outside_the_controller_still_sets_the_flag(): void
    {
        $officer = $this->officer();

        // Seeders write exits straight to the model; the flag lives there so
        // it cannot be bypassed.
        StaffExit::create([
            'basic_information_ulid' => $officer->ulid,
            'exit_type' => 'death',
            'effective_date' => '2025-09-03',
            'gratuity_status' => 'pending',
            'pension_status' => 'pending',
        ]);

        $this->assertSame(1, $officer->fresh()->is_deceased);
    }

    public function test_the_scopes_select_the_right_officers(): void
    {
        $this->officer(['surname' => 'Dead', 'is_deceased' => 1]);
        $this->officer(['surname' => 'Retired', 'is_retired' => 1]);
        $this->officer(['surname' => 'Serving']);

        $this->assertSame(['Dead'], BasicInformation::deceased()->pluck('surname')->all());
        $this->assertSame(['Retired'], BasicInformation::retired()->pluck('surname')->all());
    }
}
