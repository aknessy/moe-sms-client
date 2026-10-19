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

class StaffController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    private string $uri;
    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    public function index(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/index', [
            'page_title' => self::$page_title . ' - Staff',
            'active_page' => 'index',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function edu_progress(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/edu_progress', [
            'page_title' => self::$page_title . ' - Edu Progress',
            'active_page' => 'edu-progress',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function promotions(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/promotions', [
            'page_title' => self::$page_title . ' - Promotions',
            'active_page' => 'promotion',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function leave(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/leave', [
            'page_title' => self::$page_title . ' - Leave',
            'active_page' => 'leave-record',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function next_of_kin(){
        $uri_segment = explode('/', $this->uri);

        return view('staff/next_of_kin', [
            'page_title' => self::$page_title . ' - Next of Kin',
            'active_page' => 'next-of-kin',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function referee(){
        $uri_segment = explode('/', $this->uri);
        return view('staff/referees', [
            'page_title' => self::$page_title . ' - Referees',
            'active_page' => 'referee',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
    }

    public function career(){
        $uri_segment = explode('/', $this->uri);
        return view('staff/work_career', [
            'page_title' => self::$page_title . ' - Work Career',
            'active_page' => 'career',
            'uri_segments' => $uri_segment,
            'app_version' => config('app.app_version')
        ]);
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
