<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * An officer is identified by their file number where there is one, and by the
 * establishment/computer number otherwise.
 */
class StaffNumberTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'number-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Number', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'number@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'Administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    private function officer(array $attributes): BasicInformation
    {
        return BasicInformation::create($attributes + [
            'ulid' => (string) Str::ulid(),
            'surname' => 'Officer', 'firstname' => 'Test', 'othernames' => '',
            'date_of_birth' => '1980-01-01', 'phone_number' => '08030000000',
        ]);
    }

    /** A complete submission; overrides supply the numbers under test. */
    private function wizardPayload(array $overrides): array
    {
        $crossRiver = \App\Models\State::where('name', 'Cross River')->firstOrFail();

        return $overrides + [
            'form-type' => 'basic-information',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => '',
            'department' => 'Operations', 'division' => 'Calabar HQ',
            'designation' => 'Electoral Officer', 'grade-level' => 'GL 09', 'step' => 'Step 4',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000001',
            'email' => 'bassey.eyo@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ];
    }

    public function test_the_file_number_is_preferred_when_both_are_present(): void
    {
        $officer = $this->officer([
            'file_number' => 'MOE/F/1000',
            'computer_number' => 'INEC/CRS/1000',
        ]);

        $this->assertSame('MOE/F/1000', $officer->staff_number);
    }

    public function test_the_computer_number_stands_in_when_there_is_no_file_number(): void
    {
        $this->assertSame('INEC/CRS/1001', $this->officer([
            'file_number' => null,
            'computer_number' => 'INEC/CRS/1001',
        ])->staff_number);

        // An empty string is as good as absent.
        $this->assertSame('INEC/CRS/1002', $this->officer([
            'file_number' => '',
            'computer_number' => 'INEC/CRS/1002',
        ])->staff_number);
    }

    public function test_the_file_number_stands_alone_when_there_is_no_computer_number(): void
    {
        $this->assertSame('MOE/F/1003', $this->officer([
            'file_number' => 'MOE/F/1003',
            'computer_number' => null,
        ])->staff_number);
    }

    public function test_a_file_number_alone_is_enough_to_create_a_record(): void
    {
        // The establishment/computer number is the one optional identifier.
        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $this->wizardPayload(['file-number' => 'MOE/F/2000']))
            ->assertRedirect(route('edu-progress'));

        $this->assertDatabaseHas('basic_informations', [
            'file_number' => 'MOE/F/2000',
            'computer_number' => null,
        ]);
    }

    public function test_a_record_without_a_file_number_is_rejected(): void
    {
        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $this->wizardPayload(['computer-number' => 'INEC/CRS/2001']))
            ->assertSessionHasErrors('file-number');

        // The acting account has a staff record of its own, so check for the
        // submitted one specifically.
        $this->assertDatabaseMissing('basic_informations', ['computer_number' => 'INEC/CRS/2001']);
    }

    public function test_a_duplicate_file_number_is_rejected(): void
    {
        $this->officer(['file_number' => 'MOE/F/3000', 'computer_number' => 'INEC/CRS/3000']);

        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $this->wizardPayload(['file-number' => 'MOE/F/3000']))
            ->assertSessionHasErrors('file-number');

        $this->assertSame(1, BasicInformation::where('file_number', 'MOE/F/3000')->count());
    }

    public function test_an_officer_is_found_by_either_number(): void
    {
        $officer = $this->officer([
            'file_number' => 'MOE/F/4000',
            'computer_number' => 'INEC/CRS/4000',
        ]);

        $this->assertSame($officer->id, BasicInformation::whereStaffNumber('MOE/F/4000')->first()->id);
        $this->assertSame($officer->id, BasicInformation::whereStaffNumber('INEC/CRS/4000')->first()->id);
    }

    public function test_the_listings_search_on_either_number(): void
    {
        $this->officer([
            'file_number' => 'MOE/F/5000',
            'computer_number' => 'INEC/CRS/5000',
            'surname' => 'Searchable',
        ]);

        foreach (['MOE/F/5000', 'INEC/CRS/5000'] as $term) {
            $this->actingAs($this->actor())
                ->get(route('nominal-roll', ['q' => $term]))
                ->assertOk()
                ->assertSee('Searchable');
        }
    }

    public function test_the_listings_show_the_file_number_in_preference(): void
    {
        $this->officer([
            'file_number' => 'MOE/F/6000',
            'computer_number' => 'INEC/CRS/6000',
            'surname' => 'Preferred',
        ]);

        $response = $this->actingAs($this->actor())->get(route('bio-data'));

        $response->assertSee('MOE/F/6000');
        $response->assertDontSee('INEC/CRS/6000');
    }

    public function test_a_listing_falls_back_to_the_computer_number(): void
    {
        $this->officer(['computer_number' => 'INEC/CRS/7000', 'surname' => 'Fallback']);

        $this->actingAs($this->actor())
            ->get(route('bio-data'))
            ->assertSee('INEC/CRS/7000');
    }
}
