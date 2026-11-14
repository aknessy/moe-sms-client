<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\PromotionBrief;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Performance Appraisal opens only on the officers the Registry has put up
 * for promotion in the current year. There is nothing to report on until
 * that year's brief has been prepared, and nobody outside it to report on
 * once it has.
 */
class AppraisalPromotionBriefGateTest extends TestCase
{
    use RefreshDatabase;

    private ?User $actor = null;

    private function actor(): User
    {
        return $this->actor ??= User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
        ]);
    }

    private function putOnBrief(BasicInformation $staff): void
    {
        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);
    }

    private function openOn(?BasicInformation $staff = null)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal',
            array_filter(['staff' => $staff?->ulid])));
    }

    /* -------------------------------------------- nothing prepared yet */

    public function test_no_brief_means_the_page_is_overlaid(): void
    {
        $this->openOn()
            ->assertSee('No ' . now()->year . ' Promotion Brief has been prepared.')
            ->assertSee('brief before appraising anyone');
    }

    public function test_no_brief_means_no_officers_to_choose(): void
    {
        $this->officer();

        $this->openOn()->assertDontSee('Asuquo, Bassey');
    }

    public function test_an_officer_staged_but_not_yet_put_up_does_not_count(): void
    {
        $staff = $this->officer();

        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            // Staged, not on_brief_at: worked up but not put up.
        ]);

        $this->openOn()->assertSee('No ' . now()->year . ' Promotion Brief has been prepared.');
    }

    /* ------------------------------------------------- once it is up */

    public function test_a_brief_with_one_officer_lifts_the_overlay(): void
    {
        $staff = $this->officer();
        $this->putOnBrief($staff);

        $response = $this->openOn();

        $response->assertDontSee('No ' . now()->year . ' Promotion Brief has been prepared.');
        $response->assertSee('Asuquo, Bassey');
    }

    public function test_an_officer_off_the_brief_does_not_appear_beside_one_on_it(): void
    {
        $onBrief = $this->officer(['surname' => 'Okon']);
        $this->putOnBrief($onBrief);

        $offBrief = $this->officer(['surname' => 'Effiong']);

        $this->openOn()->assertSee('Okon')->assertDontSee('Effiong');
    }

    public function test_opening_a_report_on_an_officer_off_the_brief_finds_nobody(): void
    {
        $onBrief = $this->officer();
        $this->putOnBrief($onBrief);

        $offBrief = $this->officer(['surname' => 'Effiong']);

        $this->openOn($offBrief)->assertSee('Choose the officer this report is on.');
    }

    public function test_opening_a_report_on_an_officer_on_the_brief_shows_their_section(): void
    {
        $staff = $this->officer();
        $this->putOnBrief($staff);

        $this->openOn($staff)->assertSee('For Asuquo, Bassey');
    }

    /* ----------------------------------------- posting it by hand too */

    public function test_saving_a_section_for_an_officer_off_the_brief_is_forbidden(): void
    {
        $staff = $this->officer();

        $this->actingAs($this->actor())->post(route('hr-appraisal.career-history'), [
            'staff' => $staff->ulid,
            'section' => 'career-history',
            'date_of_employment' => '2015-01-05',
        ])->assertForbidden();
    }

    public function test_saving_a_section_for_an_officer_on_the_brief_succeeds(): void
    {
        $staff = $this->officer();
        $this->putOnBrief($staff);

        $this->actingAs($this->actor())->post(route('hr-appraisal.career-history'), [
            'staff' => $staff->ulid,
            'section' => 'career-history',
            'date_of_employment' => '2015-01-05',
        ])->assertRedirect();
    }
}
