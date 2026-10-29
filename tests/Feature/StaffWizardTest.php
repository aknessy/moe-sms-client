<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\EduProgress;
use App\Models\Leave;
use App\Models\NextOfKin;
use App\Models\Promotion;
use App\Models\Referee;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffWizardTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::firstOrNew(['username' => 'wizard-tester']);
        $user->fill([
            // Personal details live on the staff record the account belongs to.
            'basic_information_ulid' => \App\Models\BasicInformation::factory()
                ->create(['surname' => 'Wizard', 'firstname' => 'Tester', 'othernames' => ''])
                ->ulid,
            'email' => 'wizard@inec.gov.ng',
            'permissions' => json_encode([]),
            'role' => 'Administrator',
            'is_active' => 1,
        ]);
        $user->password = 'password';
        $user->save();

        return $user->fresh();
    }

    private function basicInformation(string $computerNumber): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return [
            'form-type' => 'basic-information',
            // Every field bar the computer number and other names is required.
            'file-number' => 'FILE/' . $computerNumber,
            'computer-number' => $computerNumber,
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'other-names' => 'Okon',
            'department' => 'Operations', 'division' => 'Calabar HQ',
            'gender' => 'Male',
            'designation' => 'Executive Officer (EO)', 'grade-level' => 'GL 09', 'step' => 'Step 4',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04', 'phone-number' => '08030000001',
            'email' => 'bassey.eyo@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->where('lga_name', 'Calabar Municipal')->value('id'),
            'pfa' => 'Stanbic IBTC Pension', 'rsa' => 'PEN123456789', 'nhis' => '12345678',
            'address' => 'No. 4 Marian Road',
        ];
    }

    public function test_personal_information_creates_the_staff_and_opens_a_draft(): void
    {
        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $this->basicInformation('TEST/0001'))
            ->assertRedirect(route('edu-progress'));

        $this->assertDatabaseHas('basic_informations', [
            'computer_number' => 'TEST/0001',
            'surname' => 'Eyo',
            'email' => 'bassey.eyo@inec.gov.ng',
            'address' => 'No. 4 Marian Road',
            'grade_level' => 'GL 09',
            'step' => 'Step 4',
        ]);
        $this->assertNotNull(session('staff_draft_ulid'));
    }

    public function test_a_recorded_category_is_closed_and_redirects_onward(): void
    {
        $this->actingAs($this->actor())
            ->post('/dashboard/staff/create', $this->basicInformation('TEST/0002'));

        // Personal Information is captured, so it must not reopen.
        $this->actingAs($this->actor())
            ->get('/dashboard/staff/basic-info')
            ->assertRedirect(route('edu-progress'));
    }

    public function test_later_categories_are_closed_until_a_staff_record_exists(): void
    {
        $this->actingAs($this->actor())
            ->get('/dashboard/staff/leave-record')
            ->assertRedirect(route('basic-info'));
    }

    public function test_the_full_wizard_records_every_category_then_resets(): void
    {
        $user = $this->actor();

        $this->actingAs($user)->post('/dashboard/staff/create', $this->basicInformation('TEST/0003'))
            ->assertRedirect(route('edu-progress'));

        $ulid = session('staff_draft_ulid');
        $this->assertNotNull($ulid);

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'educational-progress',
            'institution-type' => ['University'], 'institution-name' => ['University of Calabar'],
            'institution-start-date' => ['2003-09-01'], 'institution-end-date' => ['2007-07-31'],
            'qual-obtained' => ['B.Sc Political Science'],
        ])->assertRedirect(route('promotion'));

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'promotion',
            'designation' => ['Electoral Officer II', 'Electoral Officer I'],
            'grade-level' => ['GL 08', 'GL 09'], 'step' => ['3', '2'],
            'effective-from' => ['2018-01-01', '2022-01-01'],
        ])->assertRedirect(route('career'));

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'work-career',
            'date-of-employment' => '2010-01-04', 'rank' => 'Electoral Officer',
            'grade-level' => 'GL 09', 'ministerial-dept' => 'Operations',
            'ministerial-dept-address' => 'INEC Calabar HQ',
        ])->assertRedirect(route('leave-record'));

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'leave',
            'leave-type' => ['Annual Leave'], 'effective-from' => ['2025-06-01'],
            'expires-on' => ['2025-06-30'], 'leave-duration' => ['30'],
        ])->assertRedirect(route('referee'));

        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'referees',
            'referee-name' => ['Dr. Grace Okon'], 'referee-address' => ['12 Ndidem Usang Iso Road'],
            'referee-phone' => ['08030000002'], 'referee-email' => ['grace.okon@example.com'],
        ])->assertRedirect(route('next-of-kin'));

        // Final category: the draft closes and the wizard returns to the start.
        $this->actingAs($user)->post('/dashboard/staff/create', [
            'form-type' => 'next-of-kin',
            'nok-name' => ['Mary Eyo'], 'nok-relationship' => ['Spouse'],
            'nok-address' => ['No. 4 Marian Road'], 'nok-phone' => ['08030000003'],
        ])->assertRedirect(route('basic-info'));

        $this->assertNull(session('staff_draft_ulid'), 'draft should be cleared once complete');

        foreach ([EduProgress::class, Promotion::class, Career::class, Leave::class, Referee::class, NextOfKin::class] as $model) {
            $this->assertTrue(
                $model::where('basic_information_ulid', $ulid)->exists(),
                class_basename($model) . ' was not recorded'
            );
        }

        // Repeating rows are stored intact.
        $this->assertCount(2, Promotion::where('basic_information_ulid', $ulid)->first()->promotion_record);
        $this->assertSame(
            'University of Calabar',
            EduProgress::where('basic_information_ulid', $ulid)->first()->educational_record[0]['institution_name']
        );

        // With no draft open, the wizard starts over at Personal Information.
        $this->actingAs($user)->get('/dashboard/staff/basic-info')->assertOk();
    }

    public function test_duplicate_computer_number_is_rejected(): void
    {
        $user = $this->actor();
        $this->actingAs($user)->post('/dashboard/staff/create', $this->basicInformation('TEST/0004'));

        session()->forget('staff_draft_ulid');

        $this->actingAs($user)
            ->from(route('basic-info'))
            ->post('/dashboard/staff/create', $this->basicInformation('TEST/0004'))
            ->assertSessionHasErrors('computer-number');

        $this->assertSame(1, BasicInformation::where('computer_number', 'TEST/0004')->count());
    }

    public function test_an_empty_repeating_form_is_rejected(): void
    {
        $user = $this->actor();
        $this->actingAs($user)->post('/dashboard/staff/create', $this->basicInformation('TEST/0005'));

        $this->actingAs($user)
            ->from(route('edu-progress'))
            ->post('/dashboard/staff/create', ['form-type' => 'educational-progress'])
            ->assertSessionHasErrors('form-type');
    }
}
