<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StateLgaReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'reference-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Reference', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'reference@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    public function test_every_state_and_lga_ships_with_the_schema(): void
    {
        $this->assertSame(37, State::count(), 'the 36 states and the FCT');
        $this->assertSame(774, Lga::count());
    }

    public function test_a_state_code_is_the_first_three_letters_of_its_name(): void
    {
        $this->assertSame('CRO', State::where('name', 'Cross River')->value('code'));
        $this->assertSame('KAN', State::where('name', 'Kano')->value('code'));
        $this->assertSame('AKW', State::where('name', 'Akwa Ibom')->value('code'));

        // Codes identify a state on their own, so they cannot collide.
        $this->assertSame(37, State::distinct()->count('code'));
    }

    public function test_every_lga_belongs_to_a_real_state(): void
    {
        $orphans = Lga::whereNotIn('state_id', State::pluck('id'))->count();

        $this->assertSame(0, $orphans);
    }

    public function test_lga_names_repeat_across_states_so_state_scoping_matters(): void
    {
        // Kano and Nasarawa State both have an LGA called Nasarawa. Resolving a
        // bare name without a state would pick an arbitrary one of the two.
        $shared = Lga::where('lga_name', 'Nasarawa')->get();

        $this->assertGreaterThan(1, $shared->count());
        $this->assertCount($shared->count(), $shared->pluck('state_id')->unique());
    }

    public function test_an_officer_resolves_to_the_name_of_their_origin(): void
    {
        $state = State::where('name', 'Cross River')->firstOrFail();
        $lga = $state->lgas()->where('lga_name', 'Odukpani')->firstOrFail();

        $officer = BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'computer_number' => 'REF/0001',
            'surname' => 'Effiong', 'firstname' => 'Bassey', 'othernames' => '',
            'date_of_birth' => '1980-01-01', 'phone_number' => '08030000000',
            'state_of_origin' => $state->id,
            'lga_of_origin' => $lga->id,
        ]);

        $this->assertSame('Cross River', $officer->state->name);
        $this->assertSame('Odukpani', $officer->lga->lga_name);
    }

    public function test_the_wizard_stores_origin_as_ids_not_names(): void
    {
        $state = State::where('name', 'Cross River')->firstOrFail();
        $lga = $state->lgas()->where('lga_name', 'Akamkpa')->firstOrFail();

        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'file-number' => 'REF/F/0002',
            'computer-number' => 'REF/0002',
            'last-name' => 'Okon', 'first-name' => 'Grace', 'other-names' => '',
            'department' => 'Operations', 'division' => 'Calabar HQ',
            'designation' => 'Electoral Officer', 'grade-level' => 'GL 09', 'step' => 'Step 4',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000002',
            'email' => 'grace.okon@inec.gov.ng',
            'state-of-origin' => $state->id,
            'lga-of-origin' => $lga->id,
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ])->assertRedirect(route('edu-progress'));

        $this->assertDatabaseHas('basic_informations', [
            'computer_number' => 'REF/0002',
            'state_of_origin' => $state->id,
            'lga_of_origin' => $lga->id,
        ]);
    }

    public function test_an_lga_from_another_state_is_rejected(): void
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();
        $lagosLga = State::where('name', 'Lagos')->firstOrFail()->lgas()->firstOrFail();

        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'computer-number' => 'REF/0003',
            'last-name' => 'Mismatch', 'first-name' => 'Test', 'other-names' => '',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000003',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $lagosLga->id,
        ])->assertSessionHasErrors('lga-of-origin');

        $this->assertDatabaseMissing('basic_informations', ['computer_number' => 'REF/0003']);
    }

    public function test_an_unknown_state_is_rejected(): void
    {
        $this->actingAs($this->actor())->post('/dashboard/staff/create', [
            'form-type' => 'basic-information',
            'computer-number' => 'REF/0004',
            'last-name' => 'Unknown', 'first-name' => 'Test', 'other-names' => '',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000004',
            'state-of-origin' => 9999,
        ])->assertSessionHasErrors('state-of-origin');
    }

    public function test_officers_can_still_be_found_by_their_state_name(): void
    {
        $state = State::where('name', 'Cross River')->firstOrFail();

        BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'computer_number' => 'REF/0005',
            'surname' => 'Findable', 'firstname' => 'Officer', 'othernames' => '',
            'date_of_birth' => '1980-01-01', 'phone_number' => '08030000005',
            'state_of_origin' => $state->id,
            'lga_of_origin' => $state->lgas()->where('lga_name', 'Ogoja')->value('id'),
        ]);

        // The column holds an id, so this only works if the search joins through.
        $this->actingAs($this->actor())
            ->get(route('nominal-roll', ['q' => 'Cross River']))
            ->assertOk()
            ->assertSee('Findable');

        $this->actingAs($this->actor())
            ->get(route('nominal-roll', ['q' => 'Ogoja']))
            ->assertOk()
            ->assertSee('Findable');
    }

    public function test_an_approved_amendment_writes_the_id_not_the_name(): void
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();
        $lagos = State::where('name', 'Lagos')->firstOrFail();

        $officer = BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'computer_number' => 'REF/0006',
            'surname' => 'Amend', 'firstname' => 'Test', 'othernames' => '',
            'date_of_birth' => '1980-01-01', 'phone_number' => '08030000006',
            'state_of_origin' => $crossRiver->id,
            'lga_of_origin' => $crossRiver->lgas()->value('id'),
        ]);

        $this->actingAs($this->actor())->post(route('record-amendments.store'), [
            'basic_information_ulid' => $officer->ulid,
            'field' => 'state_of_origin',
            'current_value' => 'Cross River',
            'requested_value' => 'Lagos',
        ])->assertRedirect(route('record-amendments'));

        $amendment = \App\Models\RecordAmendment::where('basic_information_ulid', $officer->ulid)->firstOrFail();

        $this->actingAs($this->actor())
            ->post(route('record-amendments.review', $amendment->ulid), ['decision' => 'approved']);

        $this->assertSame($lagos->id, $officer->fresh()->state_of_origin);
    }

    public function test_an_amendment_naming_no_real_state_is_refused(): void
    {
        $officer = BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'computer_number' => 'REF/0007',
            'surname' => 'Bogus', 'firstname' => 'Test', 'othernames' => '',
            'date_of_birth' => '1980-01-01', 'phone_number' => '08030000007',
            'state_of_origin' => State::where('name', 'Cross River')->value('id'),
        ]);

        $this->actingAs($this->actor())->post(route('record-amendments.store'), [
            'basic_information_ulid' => $officer->ulid,
            'field' => 'state_of_origin',
            'requested_value' => 'Brazil',
        ])->assertSessionHasErrors('requested_value');

        $this->assertDatabaseMissing('record_amendments', ['requested_value' => 'Brazil']);
    }
}
