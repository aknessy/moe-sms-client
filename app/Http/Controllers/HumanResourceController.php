<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\CharacterTraitAssessment;
use App\Models\Department;
use App\Models\EduProgress;
use App\Models\LeaveApplication;
use App\Models\Lga;
use App\Models\Notification;
use App\Models\SelfReportActivity;
use App\Models\State;
use App\Support\Appraisal;
use App\Support\LeaveLetter;
use App\Support\PostingContext;
use App\Support\PromotionDue;
use App\Support\ReportingAuthority;
use App\Support\SelfReportWindow;
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
            ? BasicInformation::with(['state', 'lga', 'eduProgress', 'promotion'])
                ->where('ulid', $selected)->first()
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
            'departments' => Department::options(),
            'qualifications' => $staff?->qualifications() ?? collect(),
            // Self Reporting runs over the years the promotion brief counts
            // before calling this officer due, so the section needs both the
            // window and what is held against each quarter of it.
            'reportingWindow' => SelfReportWindow::for($staff),
            'selfReport' => SelfReportActivity::forStaff($staff),
            // The comment beside an activity is the reporting officer's, and
            // the section holds to that rather than asking the form to.
            'mayComment' => ReportingAuthority::mayCommentOn(auth()->user(), $staff),
            // The Character Traits grade, where one has been given already.
            'characterTraits' => $staff === null ? null
                : CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)->first(),
            // Nobody has said when the present appointment began, so the
            // form opens on the day their last promotion took effect. It is
            // a suggestion until it is saved.
            'presentAppointment' => $staff === null ? null
                : ($staff->date_of_present_appointment
                    ?: PromotionDue::lastMovedOn($staff)?->toDateString()),
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
                             Rule::in(Department::names())],
            // And one of that department's units. For an officer posted out
            // the department is not on the form, so their own stands in. Left
            // blank where the department has no units yet.
            'unit' => ['nullable', 'string', 'max:120',
                       function ($attribute, $value, $fail) use ($request, $atHeadquarters, $staff) {
                           if (blank($value)) {
                               return;
                           }

                           $department = $atHeadquarters
                               ? $request->input('department')
                               : $staff->department;

                           if (! Department::hasUnit($department, $value)) {
                               $fail('That is not a unit of the chosen department.');
                           }
                       }],
        ], [
            'state_of_origin.required' => 'Choose the state the officer hails from.',
            'department.required' => 'Choose the department the officer belongs to.',
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
            'unit' => $data['unit'] ?? null,
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

    /** The officer's service dates, from the Career History section. */
    public function store_appraisal_career_history(Request $request)
    {
        $staff = BasicInformation::where('ulid', $request->input('staff'))->firstOrFail();

        $data = $request->validate([
            'staff' => ['required', 'exists:basic_informations,ulid'],
            'date_of_employment' => ['required', 'date'],
            // An officer on probation has not been confirmed yet.
            'date_of_confirmation' => ['nullable', 'date', 'after_or_equal:date_of_employment'],
            'date_of_present_appointment' => ['nullable', 'date', 'after_or_equal:date_of_employment'],
            // Most officers hold no acting appointment at all.
            'date_of_acting_appointment' => ['nullable', 'date', 'after_or_equal:date_of_employment'],
        ], [
            'date_of_employment.required' => 'Give the day the officer first joined the service.',
            'date_of_confirmation.after_or_equal' =>
                'An appointment cannot be confirmed before it began.',
            'date_of_present_appointment.after_or_equal' =>
                'The present appointment cannot predate the first one.',
            'date_of_acting_appointment.after_or_equal' =>
                'An acting appointment cannot predate the first one.',
        ]);

        // The mode of retirement follows the date of employment; the model
        // recalculates it as the record is saved.
        $staff->update(collect($data)->except('staff')->all());

        return $this->onwardFrom($request, $staff, 'Career history saved.');
    }

    /** What the officer has qualified in, from the Qualifications section. */
    public function store_appraisal_qualifications(Request $request)
    {
        $staff = BasicInformation::where('ulid', $request->input('staff'))->firstOrFail();

        $request->validate([
            'staff' => ['required', 'exists:basic_informations,ulid'],
            // The repeating rows. A row counts for nothing without the
            // qualification itself, so that is the only one asked for.
            'qual_name' => ['nullable', 'array'],
            'qual_name.*' => ['nullable', 'string', 'max:160'],
            'qual_type.*' => ['nullable', 'string', 'max:80'],
            'qual_institution.*' => ['nullable', 'string', 'max:160'],
            'qual_year.*' => ['nullable', 'integer', 'between:1950,' . (now()->year + 1)],
        ], [
            'qual_year.*.between' => 'That is not a year a qualification could have been obtained in.',
        ]);

        $this->storeQualifications($request, $staff);

        return $this->onwardFrom($request, $staff, 'Qualifications saved.');
    }

    /**
     * What the officer did and what was made of it, from Self Reporting.
     *
     * The section asks after three years of three quarters each, and the
     * whole of it is on the one form, so one save carries the lot. A quarter
     * the officer has not reached yet is left blank and nothing is said about
     * it; a quarter they have started is answered properly or not at all.
     */
    public function store_appraisal_self_reporting(Request $request)
    {
        $staff = BasicInformation::where('ulid', $request->input('staff'))->firstOrFail();

        // The comments are the reporting officer's, so an account that may
        // not write them may not send them either — a disabled field on the
        // form would be nothing more than a suggestion.
        if (! ReportingAuthority::mayCommentOn(auth()->user(), $staff)
            && collect((array) $request->input('comment', []))
                ->flatten()->filter()->isNotEmpty()) {

            abort(403, 'Only the reporting officer writes those comments.');
        }

        $request->validate([
            'staff' => ['required', 'exists:basic_informations,ulid'],
            'activity' => ['nullable', 'array'],
            'activity.*.*.*' => ['nullable', 'string', 'max:1000'],
            'comment.*.*.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $quarters = $this->selfReportQuarters($request);
        $window = SelfReportWindow::for($staff);

        // An officer is to describe at least four activities in a quarter.
        // Said of a quarter they have not reached yet it would be nonsense,
        // so it is only said of the ones they have written in.
        $errors = [];

        foreach ($quarters as $key => $activities) {
            [$year, $quarter] = explode('.', $key);

            // The section only ever offered the years the window covers, so
            // anything else came from somewhere other than the form.
            if (! $window->covers((int) $year)
                || ! array_key_exists((int) $quarter, SelfReportActivity::QUARTERS)) {

                $errors["activity.{$key}"] = 'That is not a quarter this report covers.';
                continue;
            }

            if (count($activities) < SelfReportActivity::LEAST_ACTIVITIES) {
                $errors["activity.{$key}"] = sprintf(
                    'Describe at least %d activities for %s %s, or leave the quarter empty.',
                    SelfReportActivity::LEAST_ACTIVITIES,
                    SelfReportActivity::QUARTERS[(int) $quarter],
                    $year,
                );
            }
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        $this->storeSelfReport($staff, $quarters);

        return $this->onwardFrom($request, $staff, 'Self reporting saved.');
    }

    /**
     * The Character Traits section: a grade for each of dependability,
     * appearance, attendance and resource utilization, and one justification
     * covering all four together.
     */
    public function store_appraisal_character_traits(Request $request)
    {
        $staff = BasicInformation::where('ulid', $request->input('staff'))->firstOrFail();

        // The grades are the reporting officer's to give: the Electoral
        // Officer of the LGA the officer is deployed to, the head of the
        // department they belong to, or an administrator — the same desks
        // that answer for the comments in Self Reporting.
        if (! ReportingAuthority::mayCommentOn(auth()->user(), $staff)) {
            abort(403, "Character Traits is graded by the officer's reporting officer.");
        }

        $rules = ['staff' => ['required', 'exists:basic_informations,ulid']];
        $messages = [];

        foreach (CharacterTraitAssessment::TRAITS as $column => $trait) {
            $rules[$column] = ['required', Rule::in(CharacterTraitAssessment::GRADES)];
            $messages["{$column}.required"] = "Choose a grade for {$trait['label']}.";
        }

        $rules['justification'] = ['required', 'string', 'max:1000'];
        $messages['justification.required'] = 'Say why these grades were given.';

        $data = $request->validate($rules, $messages);

        CharacterTraitAssessment::updateOrCreate(
            ['basic_information_ulid' => $staff->ulid],
            collect($data)->except('staff')->all(),
        );

        return $this->onwardFrom($request, $staff, 'Character traits saved.');
    }

    /**
     * The activities the form sent back, gathered under the quarter they
     * belong to and keyed "year.quarter".
     *
     * The rows arrive as `activity[year][quarter][]` with the comments
     * alongside them, lining up by position — which is what lets a row be
     * cloned without renumbering anything. An activity nobody described is
     * not an activity, and a quarter with none of them is not reported on.
     */
    private function selfReportQuarters(Request $request): array
    {
        $gathered = [];

        foreach ((array) $request->input('activity', []) as $year => $quarters) {
            foreach ((array) $quarters as $quarter => $activities) {
                foreach ((array) $activities as $row => $activity) {
                    if (blank($activity)) {
                        continue;
                    }

                    $gathered["{$year}.{$quarter}"][] = [
                        'activity' => trim($activity),
                        'comment' => $request->input("comment.{$year}.{$quarter}.{$row}") ?: null,
                    ];
                }
            }
        }

        return $gathered;
    }

    /**
     * Write back the quarters the section was showing.
     *
     * What the form showed is the whole of it, so what it sends back replaces
     * what was there — including a quarter emptied out, which is how an
     * activity is taken off.
     */
    private function storeSelfReport(BasicInformation $staff, array $quarters): void
    {
        SelfReportActivity::where('basic_information_ulid', $staff->ulid)->delete();

        foreach ($quarters as $key => $activities) {
            [$year, $quarter] = explode('.', $key);

            foreach (array_values($activities) as $position => $activity) {
                SelfReportActivity::create($activity + [
                    'basic_information_ulid' => $staff->ulid,
                    'year' => (int) $year,
                    'quarter' => (int) $quarter,
                    'position' => $position,
                ]);
            }
        }
    }

    /** Back to the report, at the section after the one just saved. */
    private function onwardFrom(Request $request, BasicInformation $staff, string $message)
    {
        return redirect()->route('hr-appraisal', array_filter([
            'section' => Appraisal::next($request->input('section')),
            'staff' => $staff->ulid,
        ]))->with('status', $message);
    }

    /**
     * Write back the qualifications the section was showing.
     *
     * The rows arrive as parallel arrays and line up by position, which is
     * what lets a row be cloned without renumbering anything. A row with no
     * qualification named is not a row.
     *
     * The form carries the schooling dates through untouched, so editing a
     * qualification here does not throw away what the wizard recorded about
     * the institution it came from.
     */
    private function storeQualifications(Request $request, BasicInformation $staff): void
    {
        // Absent entirely means the section was not showing them, so there is
        // nothing to say about them either way.
        if (! $request->has('qual_name')) {
            return;
        }

        $entries = [];

        foreach ((array) $request->input('qual_name', []) as $row => $qualification) {
            if (blank($qualification)) {
                continue;
            }

            $entries[] = array_filter([
                'institution_type' => $request->input("qual_type.{$row}"),
                'institution_name' => $request->input("qual_institution.{$row}"),
                'qualification' => $qualification,
                'year_obtained' => $request->input("qual_year.{$row}"),
                'start_date' => $request->input("qual_start.{$row}"),
                'end_date' => $request->input("qual_end.{$row}"),
            ], fn ($value) => filled($value));
        }

        // What the form showed is the whole of it, so what it sends back
        // replaces what was there.
        EduProgress::where('basic_information_ulid', $staff->ulid)->delete();

        if ($entries !== []) {
            EduProgress::create([
                'basic_information_ulid' => $staff->ulid,
                'educational_record' => $entries,
            ]);
        }
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
