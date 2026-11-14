<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\PromotionBrief;
use App\Models\Sanction;
use App\Models\StaffQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Sanctions section: a caution or query already on file for the officer
 * is shown instead of asking anything; where there is none, a Yes/No
 * question on disciplinary action during the period covered, with the
 * detail asked for only where the answer is yes.
 */
class AppraisalSanctionsTest extends TestCase
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
            'surname' => 'Okoro', 'firstname' => 'Chidi',
        ]);

        $this->putOnBrief($staff);

        return $staff;
    }

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'sanctions', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.sanctions'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'sanctions',
                'taken' => 'no',
            ], $overrides));
    }

    /* --------------------------------------------- a query already on file */

    public function test_a_query_on_file_is_shown_instead_of_the_question(): void
    {
        $staff = $this->officer();

        StaffQuery::create([
            'basic_information_ulid' => $staff->ulid,
            'query_date' => now()->subMonths(2)->toDateString(),
            'offence' => 'Absenteeism without leave',
            'sanction' => 'warning',
            'status' => 'closed',
        ]);

        $response = $this->section($staff);

        $response->assertSee('Absenteeism without leave');
        $response->assertSee('warning');
        $response->assertDontSee('Has any disciplinary action been taken');
    }

    public function test_more_than_one_query_on_file_shows_all_of_them(): void
    {
        $staff = $this->officer();

        StaffQuery::create([
            'basic_information_ulid' => $staff->ulid,
            'query_date' => now()->subMonths(5)->toDateString(),
            'offence' => 'Insubordination',
            'status' => 'closed',
        ]);

        StaffQuery::create([
            'basic_information_ulid' => $staff->ulid,
            'query_date' => now()->subMonth()->toDateString(),
            'offence' => 'Late submission of returns',
            'status' => 'closed',
        ]);

        $response = $this->section($staff);

        $response->assertSee('Insubordination');
        $response->assertSee('Late submission of returns');
    }

    public function test_the_question_is_not_offered_where_a_query_is_on_file(): void
    {
        $staff = $this->officer();

        StaffQuery::create([
            'basic_information_ulid' => $staff->ulid,
            'query_date' => now()->subMonth()->toDateString(),
            'offence' => 'Negligence of duty',
            'status' => 'closed',
        ]);

        $this->save($staff)->assertForbidden();
    }

    /* --------------------------------------------------------- no query on file */

    public function test_the_section_asks_the_question_where_there_is_no_query(): void
    {
        $this->section($this->officer())
            ->assertSee('Has any disciplinary action been taken against the officer');
    }

    public function test_the_section_offers_yes_and_no(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('name="taken" value="yes"', false);
        $response->assertSee('name="taken" value="no"', false);
    }

    public function test_an_answer_already_on_record_is_checked(): void
    {
        $staff = $this->officer();

        Sanction::create([
            'basic_information_ulid' => $staff->ulid,
            'taken' => true,
            'details' => 'Suspended for two weeks over the handling of sensitive materials.',
        ]);

        $html = $this->section($staff)->getContent();

        $this->assertMatchesRegularExpression(
            '/name="taken" value="yes" data-taken-option\s+checked/',
            $html,
        );
        $this->assertStringContainsString(
            'Suspended for two weeks over the handling of sensitive materials.',
            $html,
        );
    }

    /* -------------------------------------------------- saving the answer */

    public function test_a_no_answer_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'leadership-traits', 'staff' => $staff->ulid,
        ]));

        $sanction = Sanction::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertFalse($sanction->taken);
        $this->assertNull($sanction->details);
    }

    public function test_a_yes_answer_asks_for_the_detail(): void
    {
        $this->save($this->officer(), ['taken' => 'yes'])
            ->assertSessionHasErrors('details');
    }

    public function test_a_yes_answer_with_detail_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'taken' => 'yes',
            'details' => 'Reprimanded over the loss of office equipment.',
        ])->assertRedirect();

        $sanction = Sanction::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertTrue($sanction->taken);
        $this->assertSame('Reprimanded over the loss of office equipment.', $sanction->details);
    }

    public function test_saving_again_corrects_the_one_record_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['taken' => 'yes', 'details' => 'First account.']);
        $this->save($staff, ['taken' => 'yes', 'details' => 'Corrected account.']);

        $this->assertSame(1, Sanction::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('Corrected account.',
            Sanction::where('basic_information_ulid', $staff->ulid)->first()->details);
    }

    public function test_changing_the_answer_to_no_drops_the_detail_on_file(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['taken' => 'yes', 'details' => 'Reprimanded for lateness.']);
        $this->save($staff, ['taken' => 'no']);

        $sanction = Sanction::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertFalse($sanction->taken);
        $this->assertNull($sanction->details);
    }

    public function test_the_answer_is_required(): void
    {
        $this->save($this->officer(), ['taken' => ''])
            ->assertSessionHasErrors('taken');
    }

    public function test_the_answer_must_be_yes_or_no(): void
    {
        $this->save($this->officer(), ['taken' => 'maybe'])
            ->assertSessionHasErrors('taken');
    }
}
