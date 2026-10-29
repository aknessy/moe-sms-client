<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * The acting account belongs to an officer, so that officer is on the
     * register too and every total counts them. Pinned to values the tests
     * below never filter on, so they can only match what they create.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $abia = State::where('name', 'Abia')->firstOrFail();

        $staff = BasicInformation::factory()->create([
            'surname' => 'Administrator',
            'department' => 'Administration & Finance',
            'grade_level' => 'GL 15',
            'state_of_origin' => $abia->id,
            'lga_of_origin' => $abia->lgas()->value('id'),
            'date_of_birth' => '1980-01-01',
            'date_of_employment' => '2010-01-04',
        ]);

        $this->user = User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function actor(): User
    {
        return $this->user;
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes)->fresh();
    }

    /** Download a report and read the sheet back out of it. */
    private function sheet(string $route, array $query = []): array
    {
        $response = $this->actingAs($this->actor())->get(route($route, $query));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        unlink($path);

        return $rows;
    }

    /* ----------------------------------------------------------- nominal roll */

    public function test_the_nominal_roll_report_counts_the_whole_roll(): void
    {
        $this->officer(['department' => 'Operations']);
        $this->officer(['department' => 'Legal & Litigation']);

        $response = $this->actingAs($this->actor())->get(route('nominal-roll-report'));

        $response->assertOk();
        // The two created here plus the acting account's own officer.
        $this->assertSame(3, $response->viewData('total'));
        $this->assertSame(3, $response->viewData('byDepartment')->count());
    }

    public function test_the_nominal_roll_report_filters_by_department(): void
    {
        $this->officer(['department' => 'Operations', 'surname' => 'Included']);
        $this->officer(['department' => 'Legal & Litigation', 'surname' => 'Excluded']);

        $response = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report', ['department' => 'Operations']));

        $this->assertSame(1, $response->viewData('total'));
        $response->assertSee('Included');
        $response->assertDontSee('Excluded');
    }

    public function test_the_nominal_roll_report_filters_by_status(): void
    {
        $this->officer(['surname' => 'Serving']);
        $this->officer(['surname' => 'Gone', 'is_retired' => 1]);

        $active = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report', ['status' => 'active']));
        $retired = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report', ['status' => 'retired']));

        // Serving, plus the acting account's own officer.
        $this->assertSame(2, $active->viewData('total'));
        $this->assertSame(1, $retired->viewData('total'));
        $retired->assertSee('Gone');
    }

    public function test_the_nominal_roll_report_filters_by_grade_level(): void
    {
        $this->officer(['grade_level' => 'GL 08']);
        $this->officer(['grade_level' => 'GL 14']);

        $response = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report', ['grade_level' => 'GL 14']));

        $this->assertSame(1, $response->viewData('total'));
    }

    public function test_the_nominal_roll_report_filters_by_state_of_origin(): void
    {
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $kano = State::where('name', 'Kano')->firstOrFail();

        $this->officer(['state_of_origin' => $lagos->id, 'lga_of_origin' => $lagos->lgas()->value('id')]);
        $this->officer(['state_of_origin' => $kano->id, 'lga_of_origin' => $kano->lgas()->value('id')]);

        $response = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report', ['state' => $lagos->id]));

        $this->assertSame(1, $response->viewData('total'));
    }

    public function test_the_nominal_roll_downloads_and_honours_its_filters(): void
    {
        $this->officer(['department' => 'Operations', 'surname' => 'Included']);
        $this->officer(['department' => 'Legal & Litigation', 'surname' => 'Excluded']);

        $rows = $this->sheet('nominal-roll-report.export', ['department' => 'Operations']);

        $this->assertSame('File / Computer No.', $rows[0][0]);
        $this->assertCount(2, $rows, 'one officer plus the heading row');
        $this->assertSame('Included', $rows[1][1]);
    }

    /* --------------------------------------------------- retirement projection */

    public function test_the_projection_lists_officers_inside_the_window(): void
    {
        // Falls due in about two years.
        $this->officer(['date_of_birth' => now()->subYears(58)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString(),
                        'surname' => 'Soon']);
        // Decades off.
        $this->officer(['date_of_birth' => now()->subYears(30)->toDateString(),
                        'date_of_employment' => now()->subYears(2)->toDateString(),
                        'surname' => 'Later']);

        $response = $this->actingAs($this->actor())->get(route('retirement-projection'));

        $surnames = $response->viewData('rows')->map(fn ($r) => $r->staff->surname);

        $this->assertTrue($surnames->contains('Soon'));
        $this->assertFalse($surnames->contains('Later'));
    }

    public function test_the_projection_window_can_be_widened(): void
    {
        // Falls due in roughly four years: outside 24 months, inside five years.
        $this->officer(['date_of_birth' => now()->subYears(56)->toDateString(),
                        'date_of_employment' => now()->subYears(5)->toDateString(),
                        'surname' => 'FourYears']);

        $narrow = $this->actingAs($this->actor())->get(route('retirement-projection', ['window' => 12]));
        $wide = $this->actingAs($this->actor())->get(route('retirement-projection', ['window' => 60]));

        $this->assertFalse($narrow->viewData('rows')->map(fn ($r) => $r->staff->surname)->contains('FourYears'));
        $this->assertTrue($wide->viewData('rows')->map(fn ($r) => $r->staff->surname)->contains('FourYears'));
    }

    public function test_a_nonsense_window_falls_back_to_the_default(): void
    {
        $response = $this->actingAs($this->actor())
            ->get(route('retirement-projection', ['window' => 'forever']));

        $response->assertOk();
        $this->assertSame(24, $response->viewData('window'));
    }

    public function test_the_projection_leaves_out_officers_who_have_already_gone(): void
    {
        $this->officer(['date_of_birth' => now()->subYears(58)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString(),
                        'surname' => 'Serving']);
        $this->officer(['date_of_birth' => now()->subYears(58)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString(),
                        'surname' => 'Retired', 'is_retired' => 1]);
        $this->officer(['date_of_birth' => now()->subYears(58)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString(),
                        'surname' => 'Deceased', 'is_deceased' => 1]);

        $surnames = $this->actingAs($this->actor())->get(route('retirement-projection'))
            ->viewData('rows')->map(fn ($r) => $r->staff->surname);

        $this->assertSame(['Serving'], $surnames->all());
    }

    public function test_the_projection_separates_the_overdue_from_the_upcoming(): void
    {
        // Past their date but never recorded as having gone.
        $this->officer(['date_of_birth' => now()->subYears(62)->toDateString(),
                        'date_of_employment' => now()->subYears(20)->toDateString()]);
        // Falls due within the year.
        $this->officer(['date_of_birth' => now()->subYears(59)->subMonths(6)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString()]);

        $response = $this->actingAs($this->actor())->get(route('retirement-projection'));

        $this->assertSame(1, $response->viewData('overdue'));
        $this->assertSame(1, $response->viewData('withinYear'));
    }

    public function test_the_projection_downloads(): void
    {
        $this->officer(['date_of_birth' => now()->subYears(58)->toDateString(),
                        'date_of_employment' => now()->subYears(10)->toDateString(),
                        'surname' => 'Downloadable']);

        $rows = $this->sheet('retirement-projection.export');

        $this->assertSame('File / Computer No.', $rows[0][0]);
        $this->assertSame('Downloadable', $rows[1][1]);
        $this->assertSame('Retires On', $rows[0][10]);
    }

    /* ------------------------------------------------------- headcount & audit */

    public function test_the_headcount_splits_serving_from_departed(): void
    {
        $this->officer();
        $this->officer(['is_retired' => 1]);
        $this->officer(['is_deceased' => 1]);

        $response = $this->actingAs($this->actor())->get(route('headcount-report'));

        $response->assertOk();
        // The three created here plus the acting account's own officer.
        $this->assertSame(4, $response->viewData('total'));
        $this->assertSame(2, $response->viewData('active'));
        $this->assertSame(1, $response->viewData('retired'));
        $this->assertSame(1, $response->viewData('deceased'));
    }

    public function test_the_headcount_breaks_down_by_department(): void
    {
        $this->officer(['department' => 'Operations']);
        $this->officer(['department' => 'Operations', 'is_retired' => 1]);

        $byDepartment = $this->actingAs($this->actor())->get(route('headcount-report'))
            ->viewData('byDepartment');

        $this->assertSame(2, $byDepartment['Operations']->total);
        $this->assertSame(1, $byDepartment['Operations']->active);
        $this->assertSame(1, $byDepartment['Operations']->retired);
    }

    private function findings(): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($this->actor())->get(route('headcount-report'))->viewData('findings'))
            ->keyBy('finding');
    }

    public function test_the_audit_finds_records_with_no_file_number(): void
    {
        $this->officer(['file_number' => null, 'computer_number' => 'INEC/CRS/0001']);
        $this->officer();

        $this->assertSame(1, $this->findings()['No file number']->count);
    }

    public function test_the_audit_finds_records_with_no_date_of_employment(): void
    {
        $this->officer(['date_of_employment' => null]);
        $this->officer(['date_of_employment' => '2010-01-04']);

        $this->assertSame(1, $this->findings()['No date of employment']->count);
    }

    public function test_the_audit_finds_officers_past_their_date_who_are_still_serving(): void
    {
        $this->officer(['date_of_birth' => now()->subYears(62)->toDateString(),
                        'date_of_employment' => now()->subYears(20)->toDateString()]);
        $this->officer(['date_of_birth' => now()->subYears(30)->toDateString(),
                        'date_of_employment' => now()->subYears(2)->toDateString()]);

        $this->assertSame(1, $this->findings()['Retired but still counted as serving']->count);
    }

    public function test_the_audit_finds_deceased_officers_with_no_exit_recorded(): void
    {
        $withExit = $this->officer(['is_deceased' => 1]);
        StaffExit::create([
            'basic_information_ulid' => $withExit->ulid,
            'exit_type' => 'death', 'effective_date' => '2025-09-03',
            'gratuity_status' => 'pending', 'pension_status' => 'pending',
        ]);

        $this->officer(['is_deceased' => 1]);

        $this->assertSame(1, $this->findings()['Deceased with no exit recorded']->count);
    }

    public function test_a_clean_register_reports_no_findings(): void
    {
        $this->officer(['date_of_employment' => '2010-01-04']);

        $actionable = $this->findings()->reject(fn ($f) => $f->informational ?? false);

        $this->assertSame(0, $actionable->sum('count'), 'a complete record should raise nothing');
    }

    public function test_the_audit_findings_download(): void
    {
        $this->officer(['date_of_employment' => null, 'surname' => 'Incomplete']);

        $rows = $this->sheet('headcount-report.export');

        $this->assertSame('Finding', $rows[0][0]);
        $this->assertSame('No date of employment', $rows[1][0]);
        $this->assertSame('Incomplete', $rows[1][2]);
    }

    /* --------------------------------------------------------------- payroll */

    public function test_the_payroll_report_says_what_it_is_waiting_on(): void
    {
        $this->actingAs($this->actor())
            ->get(route('payroll-report'))
            ->assertOk()
            ->assertSee('Waiting on Finance/Account');
    }

    /* ------------------------------------------------------------------ auth */

    public function test_every_report_is_behind_the_dashboard_login(): void
    {
        foreach (['nominal-roll-report', 'retirement-projection', 'headcount-report', 'payroll-report',
                  'nominal-roll-report.export', 'retirement-projection.export', 'headcount-report.export'] as $route) {
            $this->get(route($route))->assertRedirect('/login');
        }
    }
}
