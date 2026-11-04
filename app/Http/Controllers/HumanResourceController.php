<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\LeaveApplication;
use App\Models\Lga;
use App\Models\Notification;
use App\Models\State;
use App\Support\Appraisal;
use App\Support\LeaveLetter;
use App\Support\PostingContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HumanResourceController extends Controller
{
    static string $page_title = 'MOE | Human Resources';

    /** How many rows a listing shows per page. */
    private const PER_PAGE = 15;

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

    /** Officers an application may be raised for, as the picker lists them. */
    private function staffOptions()
    {
        return BasicInformation::query()
            ->inActiveService()
            ->orderBy('surname')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->ulid => trim("{$s->surname}, {$s->firstname} {$s->othernames}")
                    . " ({$s->staff_number})",
            ]);
    }

    public function leave(Request $request)
    {
        // Everyone's applications, in whatever standing is asked for. Anything
        // that is not one of the statuses shows the lot.
        $status = $request->query('status');
        $filtered = in_array($status, array_keys(LeaveApplication::STATUSES), true);

        return $this->page('human-resources/leave-management', 'Leave Management', 'leave-management', [
            // Newest first: the applications waiting to be dealt with are the
            // ones just made.
            'applications' => LeaveApplication::with('staff')
                ->when($filtered, fn ($q) => $q->where('status', $status))
                ->newestFirst()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'status' => $filtered ? $status : null,
            // How many sit in each standing, so the tabs say what is behind
            // them before they are opened.
            'counts' => LeaveApplication::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'total' => LeaveApplication::count(),
            'statuses' => LeaveApplication::STATUSES,
            'leaveTypes' => LeaveApplication::TYPES,
            'staffOptions' => $this->staffOptions(),
            // What each officer's grade level entitles them to, so choosing
            // one fills the day count in without a round trip.
            'entitlements' => BasicInformation::query()->inActiveService()->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => $s->leaveDays()]),
        ]);
    }

    public function store_leave_application(Request $request)
    {
        $annual = LeaveApplication::entitlementApplies($request->input('leave_type'));

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'leave_type' => ['required', Rule::in(array_keys(LeaveApplication::TYPES))],
            'effective_date' => ['required', 'date'],
            // Annual leave runs for as long as the officer's grade level
            // allows, so it is not asked for. Every other kind runs for as
            // long as the case calls for, so it is.
            'days' => ['nullable', Rule::requiredIf(! $annual), 'integer', 'min:1', 'max:365'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'basic_information_ulid.required' => 'Choose the officer applying for leave.',
            'basic_information_ulid.exists' => 'That officer is not on the roll.',
            'leave_type.required' => 'Choose the kind of leave.',
            'days.required' => 'Say how many days the leave runs for.',
            'days.min' => 'A leave runs for at least one day.',
        ]);

        $staff = BasicInformation::where('ulid', $data['basic_information_ulid'])->first();

        // For annual leave the day count is the officer's entitlement, shown
        // readonly and taken from the record rather than from the form.
        $days = $annual ? $staff?->leaveDays() : (int) $data['days'];

        if ($days === null) {
            return back()->withInput()->withErrors([
                'basic_information_ulid' => 'That officer has no grade level on record, so '
                    . 'there is nothing to work their annual leave entitlement out from.',
            ]);
        }

        // The resumption date follows from this and the effective date; see
        // the model.
        LeaveApplication::create(array_merge($data, ['days' => $days]));

        return redirect()->route('hr-leave')->with('status', 'Leave application recorded.');
    }

    /**
     * One application, set out as the letter it would be on paper. This is the
     * reviewer's view of it — whoever it has to pass on its way up.
     */
    public function show_leave_application(string $ulid)
    {
        $application = LeaveApplication::with('staff')->where('ulid', $ulid)->firstOrFail();

        // Reaching the letter is dealing with the notice about it, however
        // they got here.
        Notification::for(auth()->user())
            ->where('url', route('hr-leave.show', $ulid))
            ->unread()
            ->update(['read_at' => now()]);

        return $this->page('human-resources/leave-letter', 'Leave Application', 'leave-management', [
            'letter' => LeaveLetter::for($application),
            'application' => $application,
            'statuses' => LeaveApplication::STATUSES,
        ]);
    }

    public function promotions()
    {
        return $this->page('human-resources/promotion-exercises', 'Promotion Exercises', 'promotion-exercises');
    }

    /**
     * The Annual Performance Evaluation Report, a section at a time.
     *
     * One route rather than thirteen: these are parts of one report on one
     * officer, so the section travels as a parameter and the officer travels
     * with it — moving between sections must not lose who is being reported
     * on.
     */
    public function appraisal(Request $request)
    {
        $section = Appraisal::resolve($request->query('section'));
        $selected = $request->query('staff');

        $staff = $selected
            ? BasicInformation::with(['state', 'lga'])->where('ulid', $selected)->first()
            : null;

        return $this->page('human-resources/performance-appraisal', 'Performance Appraisal', 'performance-appraisal', [
            'section' => $section,
            'sectionLabel' => Appraisal::label($section),
            'selected' => $selected,
            'staff' => $staff,
            'staffOptions' => $this->staffOptions(),
            // Where the next section is, and what the button to it says.
            'nextSection' => Appraisal::next($section),
            'continueLabel' => Appraisal::continueLabel($section),
            // A section with a form of its own carries its own way onward.
            'sectionHasForm' => Appraisal::hasForm($section),
            'states' => State::orderBy('name')->pluck('name', 'id')->all(),
            'departments' => DepartmentHead::options(),
            // Which office the officer sits in decides whether a department
            // is a question worth asking of them.
            'atHeadquarters' => PostingContext::atHeadquarters($staff),
            'deployment' => $staff === null ? null : PostingContext::deploymentFor($staff),
        ]);
    }

    /**
     * Correct the officer's record from the Bio-data section, then move on.
     *
     * The appraisal shows what the Registry holds, and what it holds is
     * sometimes wrong — so it may be put right here rather than sending the
     * reporting officer away to another module and back.
     */
    public function store_appraisal_bio_data(Request $request)
    {
        $staff = BasicInformation::where('ulid', $request->input('staff'))->firstOrFail();

        // A department is only asked of an officer at the state office, so
        // it is only required — and only written — when it was asked.
        $atHeadquarters = PostingContext::atHeadquarters($staff);

        $data = $request->validate([
            'staff' => ['required', 'exists:basic_informations,ulid'],
            'surname' => ['required', 'string', 'max:60'],
            'firstname' => ['required', 'string', 'max:60'],
            'othernames' => ['nullable', 'string', 'max:60'],
            'date_of_birth' => ['required', 'date'],
            'state_of_origin' => ['required', 'exists:states,id'],
            'grade_level' => ['required', 'integer',
                              'between:' . implode(',', BasicInformation::GRADE_LEVELS)],
            'step' => ['required', 'integer', 'between:1,15'],
            'department' => [Rule::requiredIf($atHeadquarters), 'nullable',
                             Rule::in(DepartmentHead::names())],
            'division' => ['required', 'string', 'max:120'],
        ], [
            'state_of_origin.required' => 'Choose the state the officer hails from.',
            'department.required' => 'Choose the department the officer belongs to.',
            'division.required' => 'Give the unit the officer serves in.',
        ]);

        // The column holds a blank rather than a null for an officer with no
        // other names.
        $changes = [
            'surname' => $data['surname'],
            'firstname' => $data['firstname'],
            'othernames' => $data['othernames'] ?? '',
            'date_of_birth' => $data['date_of_birth'],
            'state_of_origin' => $data['state_of_origin'],
            'grade_level' => $data['grade_level'],
            'step' => $data['step'],
            'division' => $data['division'],
        ];

        if ($atHeadquarters) {
            $changes['department'] = $data['department'];
        }

        // The LGA of origin belongs to a state. Moving the officer to another
        // one leaves it pointing somewhere they are not from, so it goes.
        if ((int) $data['state_of_origin'] !== (int) $staff->state_of_origin
            && ! Lga::where('id', $staff->lga_of_origin)
                    ->where('state_id', $data['state_of_origin'])->exists()) {

            $changes['lga_of_origin'] = null;
        }

        $staff->update($changes);

        $next = Appraisal::next($request->input('section'));

        return redirect()->route('hr-appraisal', array_filter([
            'section' => $next,
            'staff' => $staff->ulid,
        ]))->with('status', 'Bio-data saved.');
    }

    public function recruitment()
    {
        return $this->page('human-resources/recruitment', 'Recruitment', 'recruitment');
    }

    public function training()
    {
        return $this->page('human-resources/training', 'Training & Capacity Building', 'training');
    }

    public function establishment()
    {
        return $this->page('human-resources/establishment-strength', 'Establishment Strength', 'establishment-strength');
    }
}
