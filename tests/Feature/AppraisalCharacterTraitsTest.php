<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\CharacterTraitAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Character Traits section: a grade for each of dependability,
 * appearance, attendance and resource utilization, and one justification
 * covering all four together.
 */
class AppraisalCharacterTraitsTest extends TestCase
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

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'character-traits', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.character-traits'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'character-traits',
                'dependability_grade' => 'Very Good',
                'appearance_grade' => 'Excellent',
                'attendance_grade' => 'Very Good',
                'resource_utilization_grade' => 'Good',
                'justification' => 'Reliable, presentable, regular and makes good use of resources.',
            ], $overrides));
    }

    /* ----------------------------------------------------- the section */

    public function test_the_section_names_the_four_traits(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();

        foreach (['Dependability', 'Appearance', 'Attendance', 'Resource Utilization'] as $trait) {
            $response->assertSee($trait);
        }
    }

    public function test_each_trait_offers_the_six_grades_as_checkboxes(): void
    {
        $response = $this->section($this->officer());

        foreach (array_keys(CharacterTraitAssessment::TRAITS) as $column) {
            foreach (CharacterTraitAssessment::GRADES as $grade) {
                $response->assertSee('name="' . $column . '" value="' . $grade . '"', false);
            }
        }

        $response->assertSee('type="checkbox"', false);
    }

    public function test_grades_already_on_record_are_checked_and_the_justification_shows(): void
    {
        $staff = $this->officer();

        CharacterTraitAssessment::create([
            'basic_information_ulid' => $staff->ulid,
            'dependability_grade' => 'Good',
            'appearance_grade' => 'Very Good',
            'attendance_grade' => 'Excellent',
            'resource_utilization_grade' => 'Fair',
            'justification' => 'Steady work, could present better.',
        ]);

        $this->section($staff)->assertSee('Steady work, could present better.');
    }

    /* -------------------------------------------------- saving the grades */

    public function test_the_grades_and_justification_reach_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'commendations', 'staff' => $staff->ulid,
        ]));

        $assessment = CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertSame('Very Good', $assessment->dependability_grade);
        $this->assertSame('Excellent', $assessment->appearance_grade);
        $this->assertSame('Very Good', $assessment->attendance_grade);
        $this->assertSame('Good', $assessment->resource_utilization_grade);
        $this->assertSame('Reliable, presentable, regular and makes good use of resources.',
            $assessment->justification);
    }

    public function test_saving_again_corrects_the_one_assessment_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff);
        $this->save($staff, [
            'dependability_grade' => 'Outstanding',
            'justification' => 'Improved further since the last report.',
        ]);

        $this->assertSame(1, CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('Outstanding', CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)
            ->first()->dependability_grade);
    }

    public function test_every_trait_is_required(): void
    {
        $this->save($this->officer(), ['appearance_grade' => ''])
            ->assertSessionHasErrors('appearance_grade');
    }

    public function test_a_grade_must_be_one_of_the_six_offered(): void
    {
        $this->save($this->officer(), ['attendance_grade' => 'Terrible'])
            ->assertSessionHasErrors('attendance_grade');
    }

    public function test_the_justification_is_required(): void
    {
        $this->save($this->officer(), ['justification' => ''])
            ->assertSessionHasErrors('justification');
    }
}
