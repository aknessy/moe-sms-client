<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Confirmation;
use App\Models\Posting;
use App\Models\RecordAmendment;
use App\Models\StaffExit;
use App\Models\StaffQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    public function index()
    {
        $today = now()->startOfDay();
        $currentYear = (int) $today->year;
        $previousYear = $currentYear - 1;

        $staff = BasicInformation::query()->get();
        $exits = StaffExit::query()->get();
        $queries = StaffQuery::query()->get();

        // An officer is out of service once an exit has been recorded against them.
        $exitedUlids = $exits->pluck('basic_information_ulid')->unique();
        $active = $staff->reject(fn ($s) => $exitedUlids->contains($s->ulid));
        $retirements = $exits->where('exit_type', 'retirement');

        $retiresOn = fn ($s) => $s->date_of_birth
            ? Carbon::parse($s->date_of_birth)->startOfDay()->addYears(60)
            : null;

        // --- departments and their units -------------------------------------
        $departmentRows = $staff
            ->filter(fn ($s) => filled($s->department))
            ->groupBy('department')
            ->map(function ($group, $name) use ($exitedUlids) {
                $units = $group->pluck('division')->filter()->unique()->sort()->values();

                return (object) [
                    'department' => $name,
                    'units' => $units->count(),
                    'unit_names' => $units,
                    'headcount' => $group->count(),
                    'active' => $group->reject(fn ($s) => $exitedUlids->contains($s->ulid))->count(),
                ];
            })
            ->sortByDesc('headcount')
            ->values();

        // --- active vs retired ------------------------------------------------
        $serviceSplit = [
            ['label' => 'In active service', 'value' => $active->count(), 'colour' => '#2E7D32'],
            ['label' => 'Retired', 'value' => $retirements->count(), 'colour' => '#A5703A'],
            ['label' => 'Other exits', 'value' => $exits->count() - $retirements->count(), 'colour' => '#8C9BAB'],
        ];

        // --- retirements: last year against this one --------------------------
        $recordedIn = fn (int $year) => $retirements
            ->filter(fn ($e) => $e->effective_date && (int) Carbon::parse($e->effective_date)->year === $year)
            ->count();

        $dueIn = fn (int $year) => $staff
            ->filter(function ($s) use ($retiresOn, $year) {
                $date = $retiresOn($s);

                return $date && (int) $date->year === $year;
            })
            ->count();

        $retirementTrend = [
            ['year' => $previousYear, 'recorded' => $recordedIn($previousYear), 'due' => $dueIn($previousYear)],
            ['year' => $currentYear, 'recorded' => $recordedIn($currentYear), 'due' => $dueIn($currentYear)],
        ];

        // --- grade level spread ------------------------------------------------
        $gradeSpread = $active
            ->filter(fn ($s) => filled($s->grade_level))
            ->groupBy('grade_level')
            ->map->count()
            ->sortKeys();

        // --- discipline ---------------------------------------------------------
        $sanctioned = $queries->filter(fn ($q) => filled($q->sanction) && $q->sanction !== 'none');

        $sanctionSpread = $sanctioned
            ->groupBy('sanction')
            ->map->count()
            ->sortDesc();

        // --- things waiting on someone -------------------------------------------
        $dueWithinAYear = $staff->filter(function ($s) use ($retiresOn, $today) {
            $date = $retiresOn($s);

            return $date && $date->greaterThanOrEqualTo($today) && $date->lessThanOrEqualTo($today->copy()->addYear());
        });

        $averageAge = $staff->filter(fn ($s) => filled($s->date_of_birth))
            ->map(fn ($s) => Carbon::parse($s->date_of_birth)->diffInYears($today))
            ->avg();

        return view('dashboard/index', [
            'page_title' => self::$page_title . ' - Dashboard',
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version'),

            'totals' => [
                'staff' => $staff->count(),
                'active' => $active->count(),
                'retired' => $retirements->count(),
                'exited' => $exits->count(),
                'departments' => $staff->pluck('department')->filter()->unique()->count(),
                'units' => $staff->pluck('division')->filter()->unique()->count(),
                'duty_stations' => Posting::query()->pluck('duty_station')->filter()->unique()->count(),
                'queries' => $queries->count(),
                'open_queries' => $queries->where('status', '!=', 'closed')->count(),
                'sanctions' => $sanctioned->count(),
                'pending_amendments' => RecordAmendment::where('status', 'pending')->count(),
                'pending_confirmations' => Confirmation::where('status', 'pending')->count(),
                'postings_this_year' => Posting::query()
                    ->get()
                    ->filter(fn ($p) => $p->effective_date && (int) Carbon::parse($p->effective_date)->year === $currentYear)
                    ->count(),
                'due_within_a_year' => $dueWithinAYear->count(),
                'average_age' => $averageAge ? round($averageAge) : null,
            ],

            'departmentRows' => $departmentRows,
            'serviceSplit' => $serviceSplit,
            'retirementTrend' => $retirementTrend,
            'gradeSpread' => $gradeSpread,
            'sanctionSpread' => $sanctionSpread,
            'dueWithinAYear' => $dueWithinAYear->sortBy(fn ($s) => $retiresOn($s))->take(5),
            'retiresOn' => $retiresOn,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
        ]);
    }
}
