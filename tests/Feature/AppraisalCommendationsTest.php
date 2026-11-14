<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Commendation;
use App\Models\PromotionBrief;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Commendations section: whether the officer received a written
 * commendation during the year for outstanding performance, and what it was
 * for where they did. Open to anyone who may reach the appraisal, unlike
 * Character Traits and Self Reporting.
 */
class AppraisalCommendationsTest extends TestCase
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

    /** Only an officer on the current year's promotion brief has a report open on them. */
    private function putOnBrief(BasicInformation $staff): void
    {
        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        $staff = BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
        ]);

        $this->putOnBrief($staff);

        return $staff;
    }

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'commendations', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.commendations'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'commendations',
                'received' => 'no',
            ], $overrides));
    }

    /* ----------------------------------------------------- the section */

    public function test_the_section_asks_the_question(): void
    {
        $this->section($this->officer())
            ->assertSee('Has the officer received any special commendations');
    }

    public function test_the_section_offers_yes_and_no(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('name="received" value="yes"', false);
        $response->assertSee('name="received" value="no"', false);
    }

    public function test_an_answer_already_on_record_is_checked(): void
    {
        $staff = $this->officer();

        Commendation::create([
            'basic_information_ulid' => $staff->ulid,
            'received' => true,
            'details' => 'Commended by the Resident Electoral Commissioner for the 2025 registration exercise.',
        ]);

        $html = $this->section($staff)->getContent();

        $this->assertMatchesRegularExpression(
            '/name="received" value="yes" data-received-option\s+checked/',
            $html,
        );
        $this->assertStringContainsString(
            'Commended by the Resident Electoral Commissioner for the 2025 registration exercise.',
            $html,
        );
    }

    /* -------------------------------------------------- saving the answer */

    public function test_a_no_answer_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'sanctions', 'staff' => $staff->ulid,
        ]));

        $commendation = Commendation::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertFalse($commendation->received);
        $this->assertNull($commendation->details);
    }

    public function test_a_yes_answer_asks_for_the_detail(): void
    {
        $this->save($this->officer(), ['received' => 'yes'])
            ->assertSessionHasErrors('details');
    }

    public function test_a_yes_answer_with_detail_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'received' => 'yes',
            'details' => 'Commended for outstanding conduct of the 2025 voter registration exercise.',
        ])->assertRedirect();

        $commendation = Commendation::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertTrue($commendation->received);
        $this->assertSame('Commended for outstanding conduct of the 2025 voter registration exercise.',
            $commendation->details);
    }

    public function test_saving_again_corrects_the_one_record_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['received' => 'yes', 'details' => 'First commendation.']);
        $this->save($staff, ['received' => 'yes', 'details' => 'Corrected detail.']);

        $this->assertSame(1, Commendation::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('Corrected detail.',
            Commendation::where('basic_information_ulid', $staff->ulid)->first()->details);
    }

    public function test_changing_the_answer_to_no_drops_the_detail_on_file(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['received' => 'yes', 'details' => 'Commended for the registration exercise.']);
        $this->save($staff, ['received' => 'no']);

        $commendation = Commendation::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertFalse($commendation->received);
        $this->assertNull($commendation->details);
    }

    public function test_the_answer_is_required(): void
    {
        $this->save($this->officer(), ['received' => ''])
            ->assertSessionHasErrors('received');
    }

    public function test_the_answer_must_be_yes_or_no(): void
    {
        $this->save($this->officer(), ['received' => 'maybe'])
            ->assertSessionHasErrors('received');
    }
}
