<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\Confirmation;
use App\Models\EduProgress;
use App\Models\Leave;
use App\Models\NextOfKin;
use App\Models\Posting;
use App\Models\Promotion;
use App\Models\RecordAmendment;
use App\Models\Referee;
use App\Models\StaffExit;
use App\Models\StaffQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    private string $uri;
    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    /**
     * The Add Staff Record wizard, in the order the inner sidebar lists them.
     * Each category is captured once per staff member; `model` is what proves
     * it has been captured.
     */
    private const CATEGORIES = [
        'index' => ['label' => 'Personal Information', 'route' => 'basic-info', 'model' => null],
        'edu-progress' => ['label' => 'Educational Progression', 'route' => 'edu-progress', 'model' => EduProgress::class],
        'promotion' => ['label' => 'Promotion', 'route' => 'promotion', 'model' => Promotion::class],
        'career' => ['label' => 'Working Career', 'route' => 'career', 'model' => Career::class],
        'leave-record' => ['label' => 'Leave Record', 'route' => 'leave-record', 'model' => Leave::class],
        'referee' => ['label' => 'Referee(s)', 'route' => 'referee', 'model' => Referee::class],
        'next-of-kin' => ['label' => 'Next of Kin', 'route' => 'next-of-kin', 'model' => NextOfKin::class],
    ];

    private const DRAFT_KEY = 'staff_draft_ulid';

    /** The staff record currently being built, if any. */
    private function draft(): ?BasicInformation
    {
        $ulid = session(self::DRAFT_KEY);

        return $ulid ? BasicInformation::where('ulid', $ulid)->first() : null;
    }

    /** Category keys already captured for the draft. */
    private function completed(?BasicInformation $draft): array
    {
        if (! $draft) {
            return [];
        }

        $done = ['index'];

        foreach (self::CATEGORIES as $key => $category) {
            if ($category['model'] && $category['model']::where('basic_information_ulid', $draft->ulid)->exists()) {
                $done[] = $key;
            }
        }

        return $done;
    }

    private function firstIncomplete(array $completed): ?string
    {
        foreach (array_keys(self::CATEGORIES) as $key) {
            if (! in_array($key, $completed, true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * A captured category is closed until the whole record is finished, and the
     * later categories can't be opened before the staff member exists.
     */
    private function guard(string $category)
    {
        $draft = $this->draft();
        $completed = $this->completed($draft);

        if ($category !== 'index' && ! $draft) {
            return redirect()->route('basic-info')
                ->with('status', 'Start with Personal Information to open a new staff record.');
        }

        if (in_array($category, $completed, true)) {
            $next = $this->firstIncomplete($completed);

            if ($next === null) {
                return $this->finishDraft($draft);
            }

            return redirect()->route(self::CATEGORIES[$next]['route'])->with(
                'status',
                self::CATEGORIES[$category]['label'] . ' has already been recorded for this staff. Continue with '
                    . self::CATEGORIES[$next]['label'] . '.'
            );
        }

        return null;
    }

    /** Everything captured: close the draft so the wizard starts clean. */
    private function finishDraft(?BasicInformation $draft)
    {
        $name = $draft ? trim("{$draft->surname}, {$draft->firstname}") : 'The staff record';
        session()->forget(self::DRAFT_KEY);

        return redirect()->route('basic-info')
            ->with('status', "{$name} is complete — all categories recorded. You can start another record.");
    }

    /** View payload shared by every step of the wizard. */
    private function wizardPage(string $view, string $title, string $category)
    {
        $draft = $this->draft();
        $completed = $this->completed($draft);

        return view($view, [
            'page_title' => self::$page_title . ' - ' . $title,
            'active_page' => $category,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version'),
            'categories' => self::CATEGORIES,
            'completed' => $completed,
            'draft' => $draft,
        ]);
    }

    public function index(){
        return $this->guard('index') ?? $this->wizardPage('staff/index', 'Staff', 'index');
    }

    public function edu_progress(){
        return $this->guard('edu-progress') ?? $this->wizardPage('staff/edu_progress', 'Edu Progress', 'edu-progress');
    }

    public function promotions(){
        return $this->guard('promotion') ?? $this->wizardPage('staff/promotions', 'Promotions', 'promotion');
    }

    public function leave(){
        return $this->guard('leave-record') ?? $this->wizardPage('staff/leave', 'Leave', 'leave-record');
    }

    public function next_of_kin(){
        return $this->guard('next-of-kin') ?? $this->wizardPage('staff/next_of_kin', 'Next of Kin', 'next-of-kin');
    }

    public function referee(){
        return $this->guard('referee') ?? $this->wizardPage('staff/referees', 'Referees', 'referee');
    }

    public function career(){
        return $this->guard('career') ?? $this->wizardPage('staff/work_career', 'Work Career', 'career');
    }

    /**
     * Single entry point for every step of the wizard; `form-type` says which.
     */
    public function create(Request $request)
    {
        $formType = $request->input('form-type');

        return match ($formType) {
            'basic-information' => $this->storeBasicInformation($request),
            'educational-progress' => $this->storeRepeating($request, 'edu-progress', EduProgress::class, 'educational_record', [
                'institution-type' => 'institution_type',
                'institution-name' => 'institution_name',
                'institution-start-date' => 'start_date',
                'institution-end-date' => 'end_date',
                'qual-obtained' => 'qualification',
            ], ['institution-name' => ['required', 'string', 'max:160']]),
            'promotion' => $this->storeRepeating($request, 'promotion', Promotion::class, 'promotion_record', [
                'designation' => 'designation',
                'grade-level' => 'grade_level',
                'step' => 'step',
            ], ['designation' => ['required', 'string', 'max:160']]),
            'work-career' => $this->storeCareer($request),
            'leave' => $this->storeRepeating($request, 'leave-record', Leave::class, 'leave_record', [
                'leave-type' => 'leave_type',
                'effective-from' => 'effective_from',
                'expires-on' => 'expires_on',
                'leave-duration' => 'duration',
            ], ['leave-type' => ['required', 'string', 'max:80']]),
            'referees' => $this->storeRepeating($request, 'referee', Referee::class, 'referees_record', [
                'referee-name' => 'name',
                'referee-address' => 'address',
                'referee-phone' => 'phone',
                'referee-email' => 'email',
            ], ['referee-name' => ['required', 'string', 'max:160']]),
            'next-of-kin' => $this->storeNextOfKin($request),
            default => back()->withErrors(['form-type' => 'Unrecognised form submission.'])->withInput(),
        };
    }

    private function storeBasicInformation(Request $request)
    {
        if ($redirect = $this->guard('index')) {
            return $redirect;
        }

        $data = $request->validate([
            'computer-number' => ['required', 'string', 'max:60', 'unique:basic_informations,computer_number'],
            'last-name' => ['required', 'string', 'max:80'],
            'first-name' => ['required', 'string', 'max:80'],
            'other-names' => ['nullable', 'string', 'max:80'],
            'department' => ['nullable', 'string', 'max:120'],
            'division' => ['nullable', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'grade-level' => ['nullable', 'string', 'max:20'],
            'date-of-birth' => ['required', 'date', 'before:today'],
            'phone-number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'state-of-origin' => ['nullable', 'string', 'max:80'],
            'lga-of-origin' => ['nullable', 'string', 'max:80'],
            'pfa' => ['nullable', 'string', 'max:120'],
            'rsa' => ['nullable', 'string', 'max:60'],
            'nhis' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'computer_number' => $data['computer-number'],
            'surname' => $data['last-name'],
            'firstname' => $data['first-name'],
            'othernames' => $data['other-names'] ?? '',
            'department' => $data['department'] ?? null,
            'division' => $data['division'] ?? null,
            'designation' => $data['designation'] ?? null,
            'grade_level' => $data['grade-level'] ?? null,
            'date_of_birth' => $data['date-of-birth'],
            'phone_number' => $data['phone-number'],
            'email' => $data['email'] ?? null,
            'state_of_origin' => $data['state-of-origin'] ?? null,
            'lga_of_origin' => $data['lga-of-origin'] ?? null,
            'pfa' => $data['pfa'] ?? null,
            'rsa' => $data['rsa'] ?? null,
            'nhis' => $data['nhis'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        session([self::DRAFT_KEY => $staff->ulid]);

        return $this->advance('index', 'Personal information saved.');
    }

    /**
     * The cloneable forms post parallel arrays; zip them into one record per row.
     */
    private function storeRepeating(Request $request, string $category, string $model, string $column, array $map, array $rules)
    {
        if ($redirect = $this->guard($category)) {
            return $redirect;
        }

        $request->validate(collect($rules)
            ->mapWithKeys(fn ($rule, $field) => [$field . '.*' => $rule])
            ->all());

        $rows = collect($request->input(array_key_first($map), []))
            ->keys()
            ->map(function ($i) use ($request, $map) {
                return collect($map)
                    ->mapWithKeys(fn ($to, $from) => [$to => $request->input("{$from}.{$i}")])
                    ->all();
            })
            ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
            ->values();

        if ($rows->isEmpty()) {
            return back()->withErrors(['form-type' => 'Add at least one entry before saving.'])->withInput();
        }

        $model::create([
            'basic_information_ulid' => $this->draft()->ulid,
            $column => $rows->all(),
        ]);

        return $this->advance($category, self::CATEGORIES[$category]['label'] . ' saved.');
    }

    private function storeCareer(Request $request)
    {
        if ($redirect = $this->guard('career')) {
            return $redirect;
        }

        $data = $request->validate([
            'date-of-employment' => ['required', 'date'],
            'rank' => ['nullable', 'string', 'max:120'],
            'grade-level' => ['nullable', 'string', 'max:20'],
            'ministerial-dept' => ['nullable', 'string', 'max:160'],
            'ministerial-dept-address' => ['nullable', 'string', 'max:300'],
            'mode-of-employment' => ['nullable', 'string', 'max:80'],
        ]);

        Career::create([
            'basic_information_ulid' => $this->draft()->ulid,
            // Column name carries the original misspelling from the migration.
            'date_of_employement' => $data['date-of-employment'],
            'rank' => $data['rank'] ?? null,
            'grade_level' => $data['grade-level'] ?? null,
            'ministerial_dept' => $data['ministerial-dept'] ?? null,
            'mode_of_employment' => $data['mode-of-employment'] ?? null,
            'address_of_ministerial_dept' => $data['ministerial-dept-address'] ?? null,
        ]);

        return $this->advance('career', 'Working career saved.');
    }

    private function storeNextOfKin(Request $request)
    {
        if ($redirect = $this->guard('next-of-kin')) {
            return $redirect;
        }

        $request->validate(['nok-name.*' => ['required', 'string', 'max:160']]);

        $ulid = $this->draft()->ulid;
        $saved = 0;

        foreach ((array) $request->input('nok-name', []) as $i => $name) {
            if (blank($name)) {
                continue;
            }

            NextOfKin::create([
                'basic_information_ulid' => $ulid,
                'nok_name' => $name,
                'nok_relationship' => $request->input("nok-relationship.{$i}"),
                'nok_address' => $request->input("nok-address.{$i}"),
                'nok_phone_number' => $request->input("nok-phone.{$i}"),
            ]);

            $saved++;
        }

        if ($saved === 0) {
            return back()->withErrors(['form-type' => 'Add at least one next of kin before saving.'])->withInput();
        }

        return $this->advance('next-of-kin', 'Next of kin saved.');
    }

    /** Move to the next outstanding category, or close the record if that was the last. */
    private function advance(string $justSaved, string $message)
    {
        $draft = $this->draft();
        $next = $this->firstIncomplete($this->completed($draft));

        if ($next === null) {
            return $this->finishDraft($draft);
        }

        return redirect()->route(self::CATEGORIES[$next]['route'])
            ->with('status', $message . ' Continue with ' . self::CATEGORIES[$next]['label'] . '.');
    }

    /**
     * Shared payload every Registry page needs: the sidebar segments, the title
     * and the staff list that feeds each form's staff picker.
     */
    private function registryPage(string $view, string $title, string $active_page, array $data = []){
        return view($view, array_merge([
            'page_title' => self::$page_title . ' - ' . $title,
            'active_page' => $active_page,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version'),
            'staffOptions' => BasicInformation::query()
                ->orderBy('surname')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->computer_number})"])
                ->all(),
        ], $data));
    }

    public function nominal_roll(){
        return $this->registryPage('registry/nominal-roll', 'Nominall Roll', 'nominal-roll', [
            'staff' => BasicInformation::query()->orderBy('surname')->get(),
        ]);
    }

    public function bio_data(){
        return $this->registryPage('registry/bio-data', 'Staff Bio-data', 'bio-data', [
            'staff' => BasicInformation::query()->orderBy('surname')->get(),
        ]);
    }

    public function posting_deployment(){
        return $this->registryPage('registry/posting-deployment', 'Posting & Deployment', 'posting-deployment', [
            'postings' => Posting::with('staff')->latest('effective_date')->get(),
        ]);
    }

    public function store_posting(Request $request){
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'posting_type' => ['required', 'in:transfer,deployment,secondment'],
            'from_department' => ['nullable', 'string', 'max:120'],
            'to_department' => ['required', 'string', 'max:120'],
            'duty_station' => ['nullable', 'string', 'max:120'],
            'effective_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        Posting::create($data);

        return redirect()->route('posting-deployment')->with('status', 'Posting recorded.');
    }

    public function confirmation(){
        return $this->registryPage('registry/confirmation', 'Confirmation of Appointment', 'confirmation', [
            'confirmations' => Confirmation::with('staff')->latest('appointment_date')->get(),
        ]);
    }

    public function store_confirmation(Request $request){
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'appointment_date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:appointment_date'],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'step' => ['nullable', 'string', 'max:20'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:pending,confirmed,deferred'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        Confirmation::create($data);

        return redirect()->route('confirmation')->with('status', 'Confirmation record saved.');
    }

    public function record_of_service(Request $request){
        $selected = $request->query('staff');

        return $this->registryPage('registry/record-of-service', 'Record of Service', 'record-of-service', [
            'selected' => $selected,
            'record' => $selected
                ? BasicInformation::query()
                    ->with(['career', 'eduProgress', 'promotion', 'leave', 'referee'])
                    ->where('ulid', $selected)
                    ->first()
                : null,
            'postings' => $selected ? Posting::where('basic_information_ulid', $selected)->orderBy('effective_date')->get() : collect(),
            'queries' => $selected ? StaffQuery::where('basic_information_ulid', $selected)->orderBy('query_date')->get() : collect(),
            'exit' => $selected ? StaffExit::where('basic_information_ulid', $selected)->first() : null,
        ]);
    }

    public function query_discipline(){
        return $this->registryPage('registry/query-discipline', 'Query & Discipline', 'query-discipline', [
            'queries' => StaffQuery::with('staff')->latest('query_date')->get(),
        ]);
    }

    public function store_query(Request $request){
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'query_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'offence' => ['required', 'string', 'max:160'],
            'details' => ['nullable', 'string', 'max:2000'],
            'response_date' => ['nullable', 'date', 'after_or_equal:query_date'],
            'response' => ['nullable', 'string', 'max:2000'],
            'sanction' => ['nullable', 'in:none,caution,warning,suspension,demotion,dismissal'],
            'status' => ['required', 'in:issued,responded,closed'],
        ]);

        StaffQuery::create($data);

        return redirect()->route('query-discipline')->with('status', 'Query recorded.');
    }

    /**
     * The officer's written response, laid out as the official letter it stands
     * in for, with the outcome actions open to the reviewing officer.
     */
    public function query_response(string $ulid){
        $query = StaffQuery::with('staff')->where('ulid', $ulid)->firstOrFail();

        // The bio-data holds the officer's substantive posting; fall back to the
        // most recent posting record for staff captured before those fields existed.
        $posting = Posting::where('basic_information_ulid', $query->basic_information_ulid)
            ->latest('effective_date')
            ->first();

        return $this->registryPage('registry/query-response', 'Query Response', 'query-discipline', [
            'query' => $query,
            'department' => $query->staff?->department ?: $posting?->to_department,
            'division' => $query->staff?->division ?: $posting?->duty_station,
        ]);
    }

    public function decide_query(Request $request, string $ulid){
        $data = $request->validate([
            'decision' => ['required', 'in:satisfactory,further-action,panel,sanctioned'],
            'sanction' => ['nullable', 'in:none,caution,warning,suspension,demotion,dismissal'],
            'decision_by' => ['nullable', 'string', 'max:120'],
            'decision_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $query = StaffQuery::where('ulid', $ulid)->firstOrFail();

        // A satisfactory response closes the matter; anything else keeps it live.
        $query->update([
            'decision' => $data['decision'],
            'decision_date' => now()->toDateString(),
            'decision_by' => $data['decision_by'] ?? 'Reviewing Officer',
            'decision_remarks' => $data['decision_remarks'] ?? null,
            'sanction' => $data['decision'] === 'satisfactory' ? 'none' : ($data['sanction'] ?? $query->sanction),
            'status' => $data['decision'] === 'satisfactory' ? 'closed' : 'responded',
        ]);

        return redirect()->route('query-response', $query->ulid)
            ->with('status', 'Outcome recorded against this query.');
    }

    public function retirement_exit(){
        // Civil service exit is age 60 or 35 years of service, whichever comes first.
        $today = now()->startOfDay();

        $projection = BasicInformation::query()
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function ($s) use ($today) {
                $dob = Carbon::parse($s->date_of_birth)->startOfDay();
                $retiresOn = $dob->copy()->addYears(60);

                return (object) [
                    'staff' => $s,
                    'date_of_birth' => $dob,
                    'retires_on' => $retiresOn,
                    // Signed, so officers already past the mark sort to the top.
                    'months_left' => (int) floor($today->diffInMonths($retiresOn, false)),
                    'time_left' => $this->describeTimeLeft($today, $retiresOn),
                ];
            })
            ->filter(fn ($row) => $row->months_left <= 60)
            ->sortBy('months_left');

        return $this->registryPage('registry/retirement-exit', 'Retirement & Exit', 'retirement-exit', [
            'exits' => StaffExit::with('staff')->latest('effective_date')->get(),
            'projection' => $projection,
        ]);
    }

    /**
     * Reads as "1 year, 4 months" rather than a bare decimal.
     */
    private function describeTimeLeft(Carbon $from, Carbon $to): string
    {
        if ($to->lessThan($from)) {
            $overdue = $from->diff($to);
            $parts = $this->yearMonthParts($overdue->y, $overdue->m);

            return ($parts ? implode(', ', $parts) : 'Less than a month') . ' overdue';
        }

        $remaining = $from->diff($to);
        $parts = $this->yearMonthParts($remaining->y, $remaining->m);

        return $parts ? implode(', ', $parts) : 'Less than a month';
    }

    private function yearMonthParts(int $years, int $months): array
    {
        $parts = [];

        if ($years > 0) {
            $parts[] = $years . ' year' . ($years === 1 ? '' : 's');
        }

        if ($months > 0) {
            $parts[] = $months . ' month' . ($months === 1 ? '' : 's');
        }

        return $parts;
    }

    public function store_exit(Request $request){
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'exit_type' => ['required', 'in:retirement,resignation,dismissal,death,transfer-of-service'],
            'effective_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'gratuity_status' => ['required', 'in:pending,processing,paid'],
            'pension_status' => ['required', 'in:pending,processing,paid'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        StaffExit::create($data);

        return redirect()->route('retirement-exit')->with('status', 'Exit record saved.');
    }

    public function record_amendments(){
        return $this->registryPage('registry/record-amendments', 'Record Amendments', 'record-amendments', [
            'amendments' => RecordAmendment::with('staff')->latest()->get(),
        ]);
    }

    public function store_amendment(Request $request){
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'field' => ['required', 'string', 'max:60'],
            'current_value' => ['nullable', 'string', 'max:160'],
            'requested_value' => ['required', 'string', 'max:160'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'supporting_document' => ['nullable', 'string', 'max:160'],
        ]);

        RecordAmendment::create($data + ['status' => 'pending']);

        return redirect()->route('record-amendments')->with('status', 'Amendment request submitted for review.');
    }

    /**
     * Approve or reject a pending amendment, writing the new value onto the
     * staff record when approved.
     */
    public function review_amendment(Request $request, string $ulid){
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reviewed_by' => ['nullable', 'string', 'max:120'],
        ]);

        $amendment = RecordAmendment::where('ulid', $ulid)->firstOrFail();

        if ($data['decision'] === 'approved') {
            BasicInformation::where('ulid', $amendment->basic_information_ulid)
                ->update([$amendment->field => $amendment->requested_value]);
        }

        // `validate()` omits nullable keys that were never submitted, so default it here.
        $amendment->update([
            'status' => $data['decision'],
            'reviewed_by' => $data['reviewed_by'] ?? 'Administrator',
            'reviewed_at' => now()->toDateString(),
        ]);

        return redirect()->route('record-amendments')
            ->with('status', "Amendment {$data['decision']}.");
    }
}
