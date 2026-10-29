<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\State;
use App\Models\User;
use App\Support\SpreadsheetExport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    static string $page_title = 'MOE | Reports & Analytics';

    /** How many rows a report listing shows per page. */
    private const PER_PAGE = 20;

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    private function page(string $view, string $title, string $active_page, array $data = [])
    {
        return view($view, array_merge([
            'page_title' => self::$page_title . ' - ' . $title,
            'active_page' => $active_page,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version')
        ], $data));
    }

    /* ---------------------------------------------------------------------
     | Nominal Roll
     |---------------------------------------------------------------------*/

    /** Where an officer stands: on the roll, retired, or died in service. */
    private const STATUSES = [
        'active' => 'In active service',
        'retired' => 'Retired',
        'deceased' => 'Deceased',
    ];

    /**
     * The roll, narrowed by whichever of the filters were supplied.
     */
    private function nominalRollQuery(Request $request)
    {
        return BasicInformation::query()
            ->with(['state', 'lga'])
            ->when($request->filled('department'),
                fn ($q) => $q->where('department', $request->query('department')))
            ->when($request->filled('state'),
                fn ($q) => $q->where('state_of_origin', $request->query('state')))
            ->when($request->filled('grade_level'),
                fn ($q) => $q->where('grade_level', $request->query('grade_level')))
            ->when($request->query('status') === 'active',
                fn ($q) => $q->where('is_retired', 0)->where('is_deceased', 0))
            ->when($request->query('status') === 'retired', fn ($q) => $q->where('is_retired', 1))
            ->when($request->query('status') === 'deceased', fn ($q) => $q->where('is_deceased', 1))
            ->orderBy('surname');
    }

    public function nominalRoll(Request $request)
    {
        $query = $this->nominalRollQuery($request);
        $matching = $query->clone()->get();

        return $this->page('reports/nominal-roll-report', 'Nominal Roll Report', 'nominal-roll-report', [
            'filters' => $request->only(['department', 'state', 'grade_level', 'status']),
            'statuses' => self::STATUSES,
            'departments' => $this->distinct('department'),
            'gradeLevels' => $this->distinct('grade_level'),
            'states' => State::whereIn('id', BasicInformation::query()->distinct()->pluck('state_of_origin'))
                ->orderBy('name')->pluck('name', 'id'),

            'total' => $matching->count(),
            'byDepartment' => $this->countBy($matching, 'department'),
            'byGradeLevel' => $this->countBy($matching, 'grade_level'),
            'byState' => $matching->groupBy(fn ($s) => $s->state?->name ?: 'Not recorded')
                ->map->count()->sortDesc(),
            'byLga' => $matching->groupBy(fn ($s) => $s->lga?->lga_name ?: 'Not recorded')
                ->map->count()->sortDesc()->take(10),

            'staff' => $query->paginate(self::PER_PAGE)->withQueryString(),
        ]);
    }

    public function nominalRollExport(Request $request)
    {
        $rows = $this->nominalRollQuery($request)->get()->map(fn ($s) => [
            $s->staff_number, $s->surname, $s->firstname, $s->othernames, $s->gender,
            // Rank and designation are the same thing; the roll calls it rank.
            $s->department, $s->division, $s->rank, $s->grade_level, $s->step,
            $s->date_of_birth, $s->date_of_employment, $s->date_of_confirmation,
            $s->state?->name, $s->lga?->lga_name,
            $s->phone_number, $s->email,
            $this->statusOf($s),
        ]);

        return SpreadsheetExport::download(
            SpreadsheetExport::filename('nominal-roll', $request->query('status')),
            'Nominal Roll',
            ['File / Computer No.', 'Surname', 'First Name', 'Other Names', 'Gender', 'Department',
             'Division / Unit', 'Rank', 'Grade Level', 'Step', 'Date of Birth',
             'Date of Employment', 'Date of Confirmation', 'State of Origin', 'LGA of Origin',
             'Phone', 'Email', 'Status'],
            $rows,
        );
    }

    /* ---------------------------------------------------------------------
     | Retirement Projection
     |---------------------------------------------------------------------*/

    /** Windows the projection can be drawn over, in months. */
    private const WINDOWS = [12 => 'Next 12 months', 24 => 'Next 24 months',
                             36 => 'Next 3 years', 60 => 'Next 5 years'];

    /**
     * Everyone still serving, with the date they fall due and how far off it
     * is. Officers already retired or deceased are out of the picture.
     */
    private function projection(Request $request)
    {
        $window = (int) $request->query('window', 24);
        $window = array_key_exists($window, self::WINDOWS) ? $window : 24;

        $today = now()->startOfDay();

        $rows = BasicInformation::query()
            ->where('is_retired', 0)
            ->where('is_deceased', 0)
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function ($s) use ($today) {
                $retiresOn = $s->retiresOn();

                return (object) [
                    'staff' => $s,
                    'retires_on' => $retiresOn,
                    'months_left' => (int) floor($today->diffInMonths($retiresOn, false)),
                    'mode' => $s->mode_of_retirement_label,
                ];
            })
            ->filter(fn ($r) => $r->retires_on !== null && $r->months_left <= $window)
            ->sortBy('months_left')
            ->values();

        return [$rows, $window];
    }

    public function retirementProjection(Request $request)
    {
        [$rows, $window] = $this->projection($request);

        return $this->page('reports/retirement-projection', 'Retirement Projection', 'retirement-projection', [
            'windows' => self::WINDOWS,
            'window' => $window,
            'rows' => $rows,
            'overdue' => $rows->filter(fn ($r) => $r->months_left < 0)->count(),
            'withinYear' => $rows->filter(fn ($r) => $r->months_left >= 0 && $r->months_left <= 12)->count(),
            'laterOn' => $rows->filter(fn ($r) => $r->months_left > 12)->count(),
            'byYear' => $rows->groupBy(fn ($r) => (string) $r->retires_on->year)->map->count()->sortKeys(),
            'byMode' => $rows->groupBy(fn ($r) => $r->mode ?: 'Not recorded')->map->count(),
            'byDepartment' => $rows->groupBy(fn ($r) => $r->staff->department ?: 'Not recorded')
                ->map->count()->sortDesc(),
        ]);
    }

    public function retirementProjectionExport(Request $request)
    {
        [$rows, $window] = $this->projection($request);

        return SpreadsheetExport::download(
            SpreadsheetExport::filename('retirement-projection', $window . '-months'),
            'Retirement Projection',
            ['File / Computer No.', 'Surname', 'First Name', 'Department', 'Designation',
             'Grade Level', 'Step', 'Date of Birth', 'Date of Employment',
             'Mode of Retirement', 'Retires On', 'Months Left'],
            $rows->map(fn ($r) => [
                $r->staff->staff_number, $r->staff->surname, $r->staff->firstname,
                $r->staff->department, $r->staff->designation,
                $r->staff->grade_level, $r->staff->step,
                $r->staff->date_of_birth, $r->staff->date_of_employment,
                $r->mode, $r->retires_on->toDateString(), $r->months_left,
            ]),
        );
    }

    /* ---------------------------------------------------------------------
     | Headcount & Audit
     |---------------------------------------------------------------------*/

    /**
     * Records that are incomplete or inconsistent. Each entry is something a
     * registry officer can act on, so the count is only useful next to the
     * list of who it refers to.
     */
    private function auditFindings()
    {
        $staff = BasicInformation::with(['state', 'lga'])->get();
        $accounts = User::whereNotNull('basic_information_ulid')->pluck('basic_information_ulid');

        return collect([
            [
                'finding' => 'No file number',
                'why' => 'Filed under an establishment number only.',
                'staff' => $staff->filter(fn ($s) => blank($s->file_number)),
            ],
            [
                'finding' => 'No date of employment',
                'why' => 'Cannot be assessed for 35 years of service, so falls due at 60 by default.',
                'staff' => $staff->filter(fn ($s) => blank($s->date_of_employment)),
            ],
            [
                'finding' => 'Origin not resolved',
                'why' => 'State or LGA of origin is missing from the record.',
                'staff' => $staff->filter(fn ($s) => $s->state === null || $s->lga === null),
            ],
            [
                'finding' => 'No contact on file',
                'why' => 'Neither a phone number nor an email address.',
                'staff' => $staff->filter(fn ($s) => blank($s->phone_number) && blank($s->email)),
            ],
            [
                'finding' => 'Deceased with no exit recorded',
                'why' => 'Marked deceased but no death exit, so there is no date of death.',
                'staff' => $staff->filter(fn ($s) => $s->is_deceased
                    && ! StaffExit::where('basic_information_ulid', $s->ulid)
                        ->where('exit_type', 'death')->exists()),
            ],
            [
                'finding' => 'Retired but still counted as serving',
                'why' => 'Past their retirement date with no exit recorded.',
                'staff' => $staff->filter(fn ($s) => ! $s->is_retired && ! $s->is_deceased
                    && $s->retiresOn() !== null && $s->retiresOn()->isPast()),
            ],
            [
                'finding' => 'Holds a dashboard account',
                'why' => 'For reference when reviewing who can sign in.',
                'staff' => $staff->filter(fn ($s) => $accounts->contains($s->ulid)),
                'informational' => true,
            ],
        ])->map(function ($finding) {
            $finding['count'] = $finding['staff']->count();

            return (object) $finding;
        });
    }

    public function headcount(Request $request)
    {
        $staff = BasicInformation::with(['state', 'lga'])->get();
        $exits = StaffExit::with('staff')->get();

        $active = $staff->filter(fn ($s) => ! $s->is_retired && ! $s->is_deceased);

        return $this->page('reports/headcount-report', 'Headcount & Audit', 'headcount-report', [
            'total' => $staff->count(),
            'active' => $active->count(),
            'retired' => $staff->where('is_retired', 1)->count(),
            'deceased' => $staff->where('is_deceased', 1)->count(),

            'byDepartment' => $staff->groupBy(fn ($s) => $s->department ?: 'Not recorded')
                ->map(fn ($group) => (object) [
                    'total' => $group->count(),
                    'active' => $group->filter(fn ($s) => ! $s->is_retired && ! $s->is_deceased)->count(),
                    'retired' => $group->where('is_retired', 1)->count(),
                    'deceased' => $group->where('is_deceased', 1)->count(),
                ])->sortByDesc('total'),

            'byDivision' => $staff->groupBy(fn ($s) => $s->division ?: 'Not recorded')
                ->map->count()->sortDesc(),

            'byExitType' => $exits->groupBy('exit_type')->map->count()->sortDesc(),

            'findings' => $this->auditFindings(),
        ]);
    }

    public function headcountExport(Request $request)
    {
        $rows = $this->auditFindings()
            ->reject(fn ($f) => $f->informational ?? false)
            ->flatMap(fn ($finding) => $finding->staff->map(fn ($s) => [
                $finding->finding,
                $s->staff_number, $s->surname, $s->firstname,
                $s->department, $s->division, $s->designation,
                $this->statusOf($s),
                $finding->why,
            ]));

        return SpreadsheetExport::download(
            SpreadsheetExport::filename('headcount-audit'),
            'Audit Findings',
            ['Finding', 'File / Computer No.', 'Surname', 'First Name', 'Department',
             'Division / Unit', 'Designation', 'Status', 'Why it matters'],
            $rows,
        );
    }

    /* ---------------------------------------------------------------------
     | Shared
     |---------------------------------------------------------------------*/

    private function statusOf(BasicInformation $staff): string
    {
        return match (true) {
            (bool) $staff->is_deceased => 'Deceased',
            (bool) $staff->is_retired => 'Retired',
            default => 'In active service',
        };
    }

    /** The values actually present in a column, for a filter dropdown. */
    private function distinct(string $column)
    {
        return BasicInformation::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }

    private function countBy($staff, string $column)
    {
        return $staff->groupBy(fn ($s) => $s->{$column} ?: 'Not recorded')
            ->map->count()
            ->sortDesc();
    }

    public function payroll()
    {
        return $this->page('reports/payroll-report', 'Payroll Report', 'payroll-report');
    }
}
