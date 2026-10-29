<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private const PER_PAGE = 15;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'pagination-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Page', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'pagination@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'Administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    private function makeStaff(int $count): void
    {
        // The reference tables ship with the schema, so these are always there.
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();
        $calabarMunicipal = $crossRiver->lgas()->where('lga_name', 'Calabar Municipal')->value('id');

        for ($i = 0; $i < $count; $i++) {
            BasicInformation::create([
                'ulid' => (string) Str::ulid(),
                'computer_number' => sprintf('PAGE/%04d', $i),
                // Surnames ascend so paging order is predictable.
                'surname' => sprintf('Surname%03d', $i),
                'firstname' => 'Officer',
                'othernames' => '',
                'department' => $i % 2 === 0 ? 'Operations' : 'Legal & Litigation',
                'date_of_birth' => '1980-01-01',
                'phone_number' => '08030000000',
                'state_of_origin' => $crossRiver->id,
                'lga_of_origin' => $calabarMunicipal,
            ]);
        }
    }

    public function test_the_nominal_roll_splits_into_pages(): void
    {
        $this->makeStaff(self::PER_PAGE + 5);

        $first = $this->actingAs($this->actor())->get(route('nominal-roll'));
        $first->assertOk();
        $first->assertSee('Surname000');
        $first->assertDontSee('Surname019');   // belongs on page two

        $second = $this->actingAs($this->actor())->get(route('nominal-roll', ['page' => 2]));
        $second->assertOk();
        $second->assertSee('Surname019');
        $second->assertDontSee('Surname000');
    }

    public function test_the_serial_number_continues_across_pages(): void
    {
        $this->makeStaff(self::PER_PAGE + 3);

        // First row of page two is the 16th record overall.
        $this->actingAs($this->actor())
            ->get(route('nominal-roll', ['page' => 2]))
            ->assertSee('16');
    }

    public function test_search_spans_the_whole_roll_not_just_the_visible_page(): void
    {
        $this->makeStaff(self::PER_PAGE + 5);

        // Surname019 sits on page two, but searching must find it from page one.
        $response = $this->actingAs($this->actor())->get(route('nominal-roll', ['q' => 'Surname019']));

        $response->assertOk();
        $response->assertSee('Surname019');
        $response->assertDontSee('Surname000');
    }

    public function test_search_matches_other_columns_too(): void
    {
        $this->makeStaff(4);

        $response = $this->actingAs($this->actor())->get(route('bio-data', ['q' => 'Legal']));

        $response->assertOk();
        $response->assertSee('Surname001');     // Legal & Litigation
        $response->assertDontSee('Surname000'); // Operations
    }

    public function test_the_search_term_survives_paging(): void
    {
        $this->makeStaff(self::PER_PAGE + 5);

        $response = $this->actingAs($this->actor())->get(route('nominal-roll', ['q' => 'Surname']));

        // withQueryString() keeps ?q= on the page links.
        $response->assertSee('q=Surname', false);
    }

    public function test_listings_without_enough_rows_show_no_pager(): void
    {
        $this->makeStaff(3);

        $this->actingAs($this->actor())
            ->get(route('nominal-roll'))
            ->assertDontSee('aria-label="Pagination"', false);
    }
}
