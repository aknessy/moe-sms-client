<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\GapDelegate;
use App\Models\LeaveApplication;
use App\Models\LeaveRosterEntry;
use App\Models\Notification;
use App\Models\User;
use App\Support\GapDesk;
use App\Support\ReportingAuthority;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The Leave Roster of the Department of General Administration and
 * Procurement: when each of its officers is planned to be away. HOD GAP puts
 * it together, and so do the officers they designate Registry Officers —
 * nothing on it is signed or approved, so it is theirs to share.
 */
class LeaveRosterController extends Controller
{
    /** HOD GAP and their Registry Officers see every department's roster. */
    private function readsAll(): bool
    {
        return ReportingAuthority::atGapDesk(auth()->user());
    }

    /**
     * The department whose roster this account builds: GAP's for the GAP
     * desk, and for the head of any other department, their own.
     */
    private function department(): string
    {
        $user = auth()->user();

        abort_unless($this->readsAll() || ReportingAuthority::headsOwnDepartment($user), 403,
            'The Leave Roster is put together by each department head, and by HOD GAP and their Registry Officers for theirs.');

        $department = $this->readsAll() ? GapDesk::department($user) : $user->staff?->department;

        abort_if(blank($department), 403, 'There is no department on your record to build a roster for.');

        return $department;
    }

    private function officers(string $department)
    {
        return BasicInformation::query()
            ->inActiveService()
            ->orderBy('surname')
            ->get()
            ->filter(fn ($staff) => GapDesk::includes($staff, $department));
    }

    public function index(Request $request)
    {
        $department = $this->department();
        $year = (int) $request->query('year') ?: now()->year;

        // The GAP desk reads every department's roster; only their own is
        // built here. A department head sees their own and nothing else.
        $departments = $this->readsAll()
            ? collect(Department::names())->sort()->values()
            : collect([$department]);
        $viewing = $departments->contains($request->query('department'))
            ? $request->query('department')
            : $department;

        $officers = $this->officers($department);
        $entries = LeaveRosterEntry::with('staff')
            ->where('department', $viewing)
            ->whereYear('start_date', $year)
            ->orderBy('start_date')
            ->get();

        return view('leave-roster', [
            'page_title' => 'MOE | Staff Management - Leave Roster',
            'active_page' => 'leave-roster',
            'uri_segments' => explode('/', $request->getRequestUri()),
            'app_version' => config('app.app_version'),
            'department' => $department,
            'departments' => $departments,
            'viewing' => $viewing,
            'viewingOwn' => $viewing === $department,
            'year' => $year,
            'years' => range(now()->year - 1, now()->year + 2),
            'entries' => $entries,
            // A department that has put nothing on its roster for the year
            // can be asked to — by the desk that reads them all.
            'canRemind' => $this->readsAll() && $viewing !== $department && $entries->isEmpty(),
            'leaveTypes' => LeaveApplication::TYPES,
            'staffOptions' => $officers->mapWithKeys(fn ($s) => [
                $s->ulid => trim("{$s->surname}, {$s->firstname} {$s->othernames}")
                    . " ({$s->staff_number})",
            ]),
            // What each officer's grade level entitles them to, so choosing one
            // fills the days in without a round trip.
            'entitlements' => $officers->mapWithKeys(fn ($s) => [$s->ulid => $s->leaveDays()]),
        ]);
    }

    public function store(Request $request)
    {
        $department = $this->department();
        $annual = LeaveApplication::entitlementApplies($request->input('leave_type'));

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'leave_type' => ['required', Rule::in(array_keys(LeaveApplication::TYPES))],
            'start_date' => ['required', 'date'],
            'days' => ['nullable', Rule::requiredIf(! $annual), 'integer', 'min:1', 'max:365'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'basic_information_ulid.required' => 'Pick a staff.',
            'leave_type.required' => 'Choose the kind of leave.',
            'start_date.required' => 'Give the day the leave starts.',
            'days.required' => 'Say how many days the leave runs for.',
        ]);

        $staff = BasicInformation::where('ulid', $data['basic_information_ulid'])->firstOrFail();

        if (! GapDesk::includes($staff, $department)) {
            return back()->withInput()->withErrors([
                'basic_information_ulid' => 'That officer is not in the department, or posted to it.',
            ]);
        }

        // Annual leave runs for the officer's entitlement unless the roster
        // says otherwise; anything else must say how long.
        $days = filled($data['days'] ?? null) ? (int) $data['days'] : $staff->leaveDays();

        if ($days === null) {
            return back()->withInput()->withErrors([
                'days' => 'That officer has no grade level on record, so give the days.',
            ]);
        }

        LeaveRosterEntry::create([
            'basic_information_ulid' => $staff->ulid,
            'department' => $department,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'days' => $days,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->user()->basic_information_ulid,
        ]);

        return redirect()->route('leave-roster', ['year' => date('Y', strtotime($data['start_date']))])
            ->with('status', 'Added to the Leave Roster.');
    }

    /**
     * Tell a department's head that its roster for the year has not been
     * made. Only the GAP desk asks, and only of a department that has put
     * nothing on it.
     */
    public function remind(Request $request)
    {
        $own = $this->department();

        abort_unless($this->readsAll(), 403, 'Only HOD GAP and their Registry Officers send reminders.');

        $data = $request->validate([
            'department' => ['required', Rule::in(Department::names())],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $department = $data['department'];
        $year = (int) $data['year'];
        $back = redirect()->route('leave-roster', ['department' => $department, 'year' => $year]);

        if ($department === $own) {
            return $back->withErrors(['department' => 'That is your own roster.']);
        }

        if (LeaveRosterEntry::where('department', $department)->whereYear('start_date', $year)->exists()) {
            return $back->withErrors(['department' => "{$department} already has a roster for {$year}."]);
        }

        $recipients = $this->whoAnswersFor($department);

        if ($recipients->isEmpty()) {
            return $back->withErrors([
                'department' => "Nobody heads {$department}, or acts for its head, to be reminded.",
            ]);
        }

        Notification::send(
            $recipients,
            "Leave Roster for {$year} is outstanding",
            "HOD GAP asks that {$department} puts its {$year} Leave Roster together.",
            route('leave-roster', ['year' => $year]),
        );

        return $back->with('status', "Reminder sent to the head and officer delegate of {$department}.");
    }

    /**
     * Whoever should have made the department's roster: its head, and the
     * officers its head has designated to act with them.
     */
    private function whoAnswersFor(string $department)
    {
        $role = Department::roleFor($department);

        $heads = $role === null ? collect() : User::query()
            ->where('is_active', 1)
            ->whereHas('roleRecord', fn ($q) => $q->where('role', $role))
            ->whereHas('staff', fn ($q) => $q->where('department', $department))
            ->get();

        // Designated by someone of this department, and still holding the role.
        $delegateUlids = GapDelegate::query()
            ->whereIn('designated_by', BasicInformation::where('department', $department)->select('ulid'))
            ->pluck('basic_information_ulid');

        $delegates = User::query()
            ->where('is_active', 1)
            ->whereIn('basic_information_ulid', $delegateUlids)
            ->whereHas('roleRecord', fn ($q) => $q->where('role', GapDelegate::ROLE))
            ->get();

        return $heads->merge($delegates)->unique('id')->values();
    }

    public function remove(string $ulid)
    {
        $department = $this->department();

        $entry = LeaveRosterEntry::where('ulid', $ulid)->where('department', $department)->firstOrFail();
        $year = $entry->start_date->year;
        $entry->delete();

        return redirect()->route('leave-roster', ['year' => $year])->with('status', 'Removed from the Leave Roster.');
    }
}
