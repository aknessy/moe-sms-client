<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every field of the Add Staff Record wizard is mandatory, bar the
 * establishment/computer number, other names, and the whole working career
 * section — the post being recorded may be the officer's first job.
 */
class RequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'required-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Required', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'required@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'Administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    private function basicInformation(array $overrides = []): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return array_merge([
            'form-type' => 'basic-information',
            'file-number' => 'REQ/F/0001', 'computer-number' => 'REQ/0001',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => 'Okon',
            'department' => 'Operations', 'division' => 'Calabar HQ',
            'designation' => 'Electoral Officer', 'grade-level' => 'GL 09', 'step' => 'Step 4',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000001',
            'email' => 'bassey.eyo@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ], $overrides);
    }

    /** Drop one field at a time and confirm the submission is refused. */
    public static function mandatoryBasicFields(): array
    {
        return array_map(fn ($field) => [$field], [
            'file-number', 'last-name', 'first-name', 'department', 'division',
            'designation', 'grade-level', 'step', 'date-of-birth', 'date-of-employment', 'phone-number',
            'email', 'state-of-origin', 'lga-of-origin', 'pfa', 'rsa', 'nhis', 'address',
        ]);
    }

    #[DataProvider('mandatoryBasicFields')]
    public function test_a_mandatory_basic_information_field_cannot_be_omitted(string $field): void
    {
        $payload = $this->basicInformation();
        unset($payload[$field]);

        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $payload)
            ->assertSessionHasErrors($field);

        // Counting the submitted record specifically: the acting account has a
        // staff record of its own.
        $this->assertDatabaseMissing('basic_informations', ['file_number' => 'REQ/F/0001']);
    }

    public static function optionalBasicFields(): array
    {
        return [['computer-number'], ['other-names']];
    }

    #[DataProvider('optionalBasicFields')]
    public function test_an_optional_basic_information_field_may_be_omitted(string $field): void
    {
        $payload = $this->basicInformation();
        unset($payload[$field]);

        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('basic_informations', ['file_number' => 'REQ/F/0001']);
    }

    private function openDraft(): User
    {
        $user = $this->actor();
        $this->actingAs($user)->post('/dashboard/staff/create', $this->basicInformation());

        return $user;
    }

    /** [form-type, complete payload, field to drop] for each repeating section. */
    public static function repeatingSections(): array
    {
        return [
            'educational progress' => ['educational-progress', [
                'institution-type' => ['University'], 'institution-name' => ['UNICAL'],
                'institution-start-date' => ['2003-09-01'], 'institution-end-date' => ['2007-07-31'],
                'qual-obtained' => ['B.Sc'],
            ], 'qual-obtained'],

            'promotion' => ['promotion', [
                'designation' => ['Electoral Officer'], 'grade-level' => ['GL 08'],
                'step' => ['3'], 'effective-from' => ['2018-01-01'],
            ], 'effective-from'],

            'leave' => ['leave', [
                'leave-type' => ['Annual Leave'], 'effective-from' => ['2025-06-01'],
                'expires-on' => ['2025-06-30'], 'leave-duration' => ['30'],
            ], 'expires-on'],

            'referees' => ['referees', [
                'referee-name' => ['Dr. Grace Okon'], 'referee-address' => ['12 Ndidem Road'],
                'referee-phone' => ['08030000002'], 'referee-email' => ['grace@example.com'],
            ], 'referee-email'],

            'next of kin' => ['next-of-kin', [
                'nok-name' => ['Mary Eyo'], 'nok-relationship' => ['Spouse'],
                'nok-address' => ['No. 4 Marian Road'], 'nok-phone' => ['08030000003'],
            ], 'nok-address'],
        ];
    }

    #[DataProvider('repeatingSections')]
    public function test_a_repeating_section_refuses_an_incomplete_row(
        string $formType, array $payload, string $omitted
    ): void {
        $user = $this->openDraft();

        $payload[$omitted] = [''];

        $this->actingAs($user)
            ->post('/dashboard/staff/create', ['form-type' => $formType] + $payload)
            ->assertSessionHasErrors("{$omitted}.0");
    }

    public function test_the_working_career_section_may_be_left_entirely_blank(): void
    {
        $user = $this->openDraft();

        // Advance past the two sections that precede it.
        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'educational-progress',
            'institution-type' => ['University'], 'institution-name' => ['UNICAL'],
            'institution-start-date' => ['2003-09-01'], 'institution-end-date' => ['2007-07-31'],
            'qual-obtained' => ['B.Sc'],
        ]);
        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'promotion',
            'designation' => ['Electoral Officer'], 'grade-level' => ['GL 08'],
            'step' => ['3'], 'effective-from' => ['2018-01-01'],
        ]);

        // This is the officer's first job: there is no prior appointment.
        $this->actingAs($user)
            ->post('/dashboard/staff/create', ['form-type' => 'work-career'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('leave-record'));

        $career = Career::where('basic_information_ulid', session('staff_draft_ulid'))->first();

        $this->assertNotNull($career, 'the section should still be recorded');
        $this->assertNull($career->date_of_employement);
    }

    public function test_the_mode_of_appointment_reaches_the_record(): void
    {
        $user = $this->openDraft();

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'educational-progress',
            'institution-type' => ['University'], 'institution-name' => ['UNICAL'],
            'institution-start-date' => ['2003-09-01'], 'institution-end-date' => ['2007-07-31'],
            'qual-obtained' => ['B.Sc'],
        ]);
        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'promotion',
            'designation' => ['Electoral Officer'], 'grade-level' => ['GL 08'],
            'step' => ['3'], 'effective-from' => ['2018-01-01'],
        ]);

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'work-career',
            'date-of-employment' => '2010-01-04',
            'ministerial-dept' => 'Ministry of Education',
            'mode-of-employment' => 'Permanent',
        ]);

        $career = Career::where('basic_information_ulid', session('staff_draft_ulid'))->first();

        // The select used to post under the ministry's name, so this never saved.
        $this->assertSame('Permanent', $career->mode_of_employment);
        $this->assertSame('Ministry of Education', $career->ministerial_dept);
    }

    public function test_a_promotion_records_its_effective_date(): void
    {
        $user = $this->openDraft();

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'educational-progress',
            'institution-type' => ['University'], 'institution-name' => ['UNICAL'],
            'institution-start-date' => ['2003-09-01'], 'institution-end-date' => ['2007-07-31'],
            'qual-obtained' => ['B.Sc'],
        ]);

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'promotion',
            'designation' => ['Electoral Officer'], 'grade-level' => ['GL 08'],
            'step' => ['3'], 'effective-from' => ['2018-01-01'],
        ]);

        $record = \App\Models\Promotion::where('basic_information_ulid', session('staff_draft_ulid'))
            ->first()->promotion_record[0];

        // The date input used to post as a second grade-level, overwriting it.
        $this->assertSame('2018-01-01', $record['effective_from']);
        $this->assertSame('GL 08', $record['grade_level']);
    }
}
