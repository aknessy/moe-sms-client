<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\Confirmation;
use App\Models\EduProgress;
use App\Models\Leave;
use App\Models\Lga;
use App\Models\NextOfKin;
use App\Models\Posting;
use App\Models\Promotion;
use App\Models\RecordAmendment;
use App\Models\Referee;
use App\Models\StaffExit;
use App\Models\StaffQuery;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'states' => State::orderBy('name')->get(['id', 'name', 'code']),
            // Every LGA keyed by its state, so the dependent select can be
            // filtered in the browser without a round trip per state.
            'lgasByState' => Lga::orderBy('lga_name')
                ->get(['id', 'state_id', 'lga_name'])
                ->groupBy('state_id')
                ->map(fn ($group) => $group->map(fn ($lga) => [
                    'id' => $lga->id,
                    'name' => $lga->lga_name,
                ])->values()),
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
            ], [
                'institution-type' => ['required', 'string', 'max:80'],
                'institution-name' => ['required', 'string', 'max:160'],
                'institution-start-date' => ['required', 'date'],
                'institution-end-date' => ['required', 'date'],
                'qual-obtained' => ['required', 'string', 'max:160'],
            ]),
            'promotion' => $this->storeRepeating($request, 'promotion', Promotion::class, 'promotion_record', [
                'designation' => 'designation',
                'grade-level' => 'grade_level',
                'step' => 'step',
                'effective-from' => 'effective_from',
            ], [
                'designation' => ['required', 'string', 'max:160'],
                'grade-level' => ['required', 'string', 'max:20'],
                'step' => ['required', 'string', 'max:20'],
                'effective-from' => ['required', 'date'],
                // A newly employed officer has not been promoted yet, so the
                // section may be closed with nothing in it.
            ], 'No promotion recorded.'),
            'work-career' => $this->storeCareer($request),
            'leave' => $this->storeRepeating($request, 'leave-record', Leave::class, 'leave_record', [
                'leave-type' => 'leave_type',
                'effective-from' => 'effective_from',
                'expires-on' => 'expires_on',
                'leave-duration' => 'duration',
            ], [
                'leave-type' => ['required', 'string', 'max:80'],
                'effective-from' => ['required', 'date'],
                'expires-on' => ['required', 'date'],
                'leave-duration' => ['required', 'string', 'max:60'],
                // A newly employed officer has taken no leave yet, so the
                // section may be closed with nothing in it.
            ], 'No leave recorded.'),
            'referees' => $this->storeRepeating($request, 'referee', Referee::class, 'referees_record', [
                'referee-name' => 'name',
                'referee-address' => 'address',
                'referee-phone' => 'phone',
                'referee-email' => 'email',
            ], [
                'referee-name' => ['required', 'string', 'max:160'],
                'referee-address' => ['required', 'string', 'max:300'],
                'referee-phone' => ['required', 'string', 'max:30'],
                'referee-email' => ['required', 'email', 'max:160'],
            ]),
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
            // Every officer is filed under a file number. The establishment/
            // computer number is the one optional identifier: older records
            // carry it instead, and `staff_number` falls back to it on display.
            'file-number' => ['required', 'string', 'max:60',
                              'unique:basic_informations,file_number'],
            'computer-number' => ['nullable', 'string', 'max:60',
                                  'unique:basic_informations,computer_number'],
            'last-name' => ['required', 'string', 'max:80'],
            'first-name' => ['required', 'string', 'max:80'],
            'other-names' => ['nullable', 'string', 'max:80'],
            'gender' => ['required', Rule::in(BasicInformation::GENDERS)],
            'department' => ['required', 'string', 'max:120'],
            'division' => ['required', 'string', 'max:120'],
            // Rank and designation are the same thing, chosen from a fixed list.
            'designation' => ['required', Rule::in(BasicInformation::RANKS)],
            'grade-level' => ['required', 'string', 'max:20'],
            'step' => ['required', 'string', 'max:20'],
            'date-of-birth' => ['required', 'date', 'before:today'],
            // Joined their current employer. With the date of birth it decides
            // the mode of retirement, which the model works out on save.
            'date-of-employment' => ['required', 'date', 'before_or_equal:today', 'after:date-of-birth'],
            // An officer still on probation has not been confirmed yet.
            'date-of-confirmation' => ['nullable', 'date', 'before_or_equal:today',
                                       'after_or_equal:date-of-employment'],
            'phone-number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:160'],
            // Both are foreign keys into the national reference tables.
            'state-of-origin' => ['required', 'integer', 'exists:states,id'],
            'lga-of-origin' => ['required', 'integer', 'exists:lgas,id',
                                Rule::exists('lgas', 'id')->where(
                                    fn ($q) => $q->where('state_id', $request->input('state-of-origin'))
                                )],
            'pfa' => ['required', 'string', 'max:120'],
            'rsa' => ['required', 'string', 'max:60'],
            'nhis' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $staff = BasicInformation::create([
            'ulid' => (string) Str::ulid(),
            'file_number' => $data['file-number'] ?? null,
            'computer_number' => $data['computer-number'] ?? null,
            'surname' => $data['last-name'],
            'firstname' => $data['first-name'],
            'othernames' => $data['other-names'] ?? '',
            'gender' => $data['gender'],
            'department' => $data['department'] ?? null,
            'division' => $data['division'] ?? null,
            'designation' => $data['designation'] ?? null,
            'grade_level' => $data['grade-level'] ?? null,
            'step' => $data['step'] ?? null,
            'date_of_birth' => $data['date-of-birth'],
            'date_of_employment' => $data['date-of-employment'],
            'date_of_confirmation' => $data['date-of-confirmation'] ?? null,
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
    private function storeRepeating(Request $request, string $category, string $model, string $column, array $map, array $rules, ?string $skipMessage = null)
    {
        if ($redirect = $this->guard($category)) {
            return $redirect;
        }

        $gather = fn () => collect($request->input(array_key_first($map), []))
            ->keys()
            ->map(function ($i) use ($request, $map) {
                return collect($map)
                    ->mapWithKeys(fn ($to, $from) => [$to => $request->input("{$from}.{$i}")])
                    ->all();
            })
            ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
            ->values();

        // A section that may be empty is settled before anything is validated:
        // there is nothing to check, and an empty row would fail every rule.
        if ($skipMessage !== null && ($request->input('action') === 'skip' || $gather()->isEmpty())) {
            $model::create([
                'basic_information_ulid' => $this->draft()->ulid,
                $column => [],
            ]);

            return $this->advance($category, $skipMessage);
        }

        $request->validate(collect($rules)
            ->mapWithKeys(fn ($rule, $field) => [$field . '.*' => $rule])
            ->all());

        $rows = $gather();

        if ($rows->isEmpty()) {
            return back()->withErrors(['form-type' => 'Add at least one entry before saving.'])->withInput();
        }

        $model::create([
            'basic_information_ulid' => $this->draft()->ulid,
            $column => $rows->all(),
        ]);

        return $this->advance($category, self::CATEGORIES[$category]['label'] . ' saved.');
    }

    /**
     * An officer may have held several posts before this one, so the form
     * repeats. Nothing in it is required: this may be their first job, and
     * even a remembered employer may come without the dates.
     */
    private function storeCareer(Request $request)
    {
        if ($redirect = $this->guard('career')) {
            return $redirect;
        }

        // Column names carry the original misspelling from the migration.
        $map = [
            'date-of-employment' => 'date_of_employement',
            'rank' => 'rank',
            'grade-level' => 'grade_level',
            'ministerial-dept' => 'ministerial_dept',
            'mode-of-employment' => 'mode_of_employment',
            'ministerial-dept-address' => 'address_of_ministerial_dept',
        ];

        $request->validate([
            'date-of-employment.*' => ['nullable', 'date'],
            'rank.*' => ['nullable', 'string', 'max:120'],
            'grade-level.*' => ['nullable', 'string', 'max:20'],
            'ministerial-dept.*' => ['nullable', 'string', 'max:160'],
            'ministerial-dept-address.*' => ['nullable', 'string', 'max:300'],
            'mode-of-employment.*' => ['nullable', 'string', 'max:80'],
        ]);

        $staff = $this->draft();

        // Any one field may be left out entirely, so how many appointments
        // were submitted is however far the longest of them runs.
        $rows = collect(array_keys($map))
            ->flatMap(fn ($field) => array_keys((array) $request->input($field, [])))
            ->unique()->sort()->values()
            ->map(fn ($i) => collect($map)
                ->mapWithKeys(fn ($to, $from) => [$to => $request->input("{$from}.{$i}") ?: null])
                ->all())
            // A row nobody typed anything into is not an appointment.
            ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
            ->values();

        // Skipped outright, or left entirely blank — either way the officer
        // held no post before this one, and saying so is what closes the step.
        if ($request->input('action') === 'skip' || $rows->isEmpty()) {
            Career::create([
                'basic_information_ulid' => $staff->ulid,
                'no_previous_employment' => true,
            ]);

            return $this->advance('career', 'No previous employment recorded.');
        }

        foreach ($rows as $row) {
            Career::create($row + ['basic_information_ulid' => $staff->ulid]);
        }

        return $this->advance('career', $rows->count() === 1
            ? 'Working career saved.'
            : "Working career saved — {$rows->count()} previous appointments.");
    }

    private function storeNextOfKin(Request $request)
    {
        if ($redirect = $this->guard('next-of-kin')) {
            return $redirect;
        }

        $request->validate([
            'nok-name.*' => ['required', 'string', 'max:160'],
            'nok-relationship.*' => ['required', 'string', 'max:80'],
            'nok-address.*' => ['required', 'string', 'max:300'],
            'nok-phone.*' => ['required', 'string', 'max:30'],
        ]);

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
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->staff_number})"])
                ->all(),
        ], $data));
    }

    /** How many rows a Registry listing shows per page. */
    private const PER_PAGE = 15;

    /**
     * Free-text search across the fields someone would actually look an officer
     * up by. Runs in SQL so it spans the whole roll, not just the current page.
     */
    private function searchStaff(?string $term)
    {
        return BasicInformation::query()
            // Origin now lives in the lookups, so the listings join to it.
            ->with(['state', 'lga'])
            ->when(filled($term), function ($query) use ($term) {
                $like = '%' . trim($term) . '%';

                $query->where(function ($q) use ($like) {
                    foreach (['surname', 'firstname', 'othernames', 'file_number', 'computer_number',
                              'department', 'division', 'designation', 'grade_level'] as $column) {
                        $q->orWhere($column, 'like', $like);
                    }

                    // Searching "Cross River" must still find its officers even
                    // though the column itself only holds an id.
                    $q->orWhereHas('state', fn ($s) => $s->where('name', 'like', $like)
                                                         ->orWhere('code', 'like', $like));
                    $q->orWhereHas('lga', fn ($l) => $l->where('lga_name', 'like', $like));
                });
            })
            ->orderBy('surname');
    }

    public function nominal_roll(Request $request){
        return $this->registryPage('registry/nominal-roll', 'Nominall Roll', 'nominal-roll', [
            'search' => $request->query('q'),
            'staff' => $this->searchStaff($request->query('q'))
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function bio_data(Request $request){
        return $this->registryPage('registry/bio-data', 'Staff Bio-data', 'bio-data', [
            'search' => $request->query('q'),
            'staff' => $this->searchStaff($request->query('q'))
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    /**
     * Where an officer with nothing on record is taken to stand: this command.
     * A posting only says where someone was moved to, so until one is recorded
     * they have no state of their own — and without a state they could never
     * be deployed, a deployment being confined to the state they are in.
     */
    public static function defaultPosting(): array
    {
        $state = State::where('name', config('posting.default_state'))->first();

        $lga = $state === null ? null : Lga::where('state_id', $state->id)
            ->where('lga_name', config('posting.default_lga'))->first();

        return [
            'state_id' => $state?->id,
            'state' => $state?->name,
            'lga_id' => $lga?->id,
            'lga' => $lga?->lga_name,
            'station' => config('posting.default_station'),
        ];
    }

    /**
     * Where each officer stands right now: the state their last transfer put
     * them in, the LGA their last posting sent them to, and the station they
     * are presently at. A transfer reads the state as the one it moves them
     * out of; a deployment reads the station as the one it moves them from,
     * and is confined to the LGAs of that state.
     *
     * Anything an officer has no posting for falls back to this command.
     */
    private function postingContext()
    {
        $postings = Posting::query()
            ->orderBy('effective_date')
            ->get()
            ->groupBy('basic_information_ulid');

        $states = State::pluck('name', 'id');
        $lgas = Lga::pluck('lga_name', 'id');
        $default = self::defaultPosting();

        return BasicInformation::query()->get()
            ->mapWithKeys(function ($staff) use ($postings, $states, $lgas, $default) {
                $theirs = $postings[$staff->ulid] ?? collect();

                // The state they are in is wherever their last transfer sent
                // them; the LGA, wherever their last posting of either kind did.
                $state = $theirs->where('posting_type', 'transfer')
                    ->whereNotNull('to_state')->last()?->to_state;

                $lga = $theirs->whereNotNull('to_lga')->last()?->to_lga;

                // A transfer names the station it sends them to; a deployment
                // does not, so the LGA it sent them to is where they now are.
                $latest = $theirs->last();
                $station = $latest?->duty_station
                    ?: ($latest?->to_lga ? ($lgas[$latest->to_lga] ?? null) : null);

                return [$staff->ulid => [
                    'state_id' => $state ?: $default['state_id'],
                    'state' => $state ? ($states[$state] ?? null) : $default['state'],
                    'lga_id' => $lga ?: $default['lga_id'],
                    'lga' => $lga ? ($lgas[$lga] ?? null) : $default['lga'],
                    'station' => $station ?: $default['station'],
                ]];
            });
    }

    public function posting_deployment(){
        return $this->registryPage('registry/posting-deployment', 'Posting & Deployment', 'posting-deployment', [
            'postings' => Posting::with(['staff', 'fromStateRecord', 'toStateRecord', 'toLgaRecord'])
                ->latest('effective_date')->paginate(self::PER_PAGE)->withQueryString(),
            'postingTypes' => Posting::TYPES,
            'deploymentRoles' => Posting::DEPLOYMENT_ROLES,
            'states' => State::orderBy('name')->pluck('name', 'id'),
            'lgasByState' => Lga::orderBy('lga_name')->get(['id', 'state_id', 'lga_name'])
                ->groupBy('state_id')
                ->map(fn ($group) => $group->map(fn ($lga) => [
                    'id' => $lga->id,
                    'name' => $lga->lga_name,
                ])->values()),
            'postingContext' => $this->postingContext(),
        ]);
    }

    public function store_posting(Request $request){
        $type = $request->input('posting_type');

        // A deployment stays inside the state the officer is already in, so
        // that is the state its LGA has to belong to.
        $context = $this->postingContext()[$request->input('basic_information_ulid')] ?? null;
        $deploymentState = $context['state_id'] ?? null;

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'posting_type' => ['required', Rule::in(array_keys(Posting::TYPES))],
            'effective_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'remarks' => ['nullable', 'string', 'max:1000'],

            // Transfer.
            'to_state' => ['exclude_unless:posting_type,transfer', 'required', 'exists:states,id'],
            'duty_station' => ['exclude_unless:posting_type,transfer', 'required', 'string', 'max:120'],

            // Deployment.
            'deployed_as' => ['exclude_unless:posting_type,deployment', 'required',
                              Rule::in(array_keys(Posting::DEPLOYMENT_ROLES))],

            // Both, but each against its own state.
            'to_lga' => ['required', 'exists:lgas,id',
                         Rule::exists('lgas', 'id')->where(fn ($q) => $q->where(
                             'state_id',
                             $type === 'transfer' ? $request->input('to_state') : $deploymentState,
                         ))],
        ], [
            'to_lga.exists' => $type === 'transfer'
                ? 'That LGA does not belong to the state being transferred to.'
                : 'That LGA does not belong to the state the officer is posted in.',
            'deployed_as.required' => 'Say what the officer is being deployed as.',
        ]);

        // Taken from the record rather than the form: both are shown readonly,
        // and a readonly field is still submitted.
        $data['from_state'] = $type === 'transfer' ? ($context['state_id'] ?? null) : null;
        $data['present_station'] = $type === 'deployment' ? ($context['station'] ?? null) : null;

        Posting::create($data);

        return redirect()->route('posting-deployment')->with('status', $type === 'transfer'
            ? 'Transfer recorded.'
            : 'Deployment recorded.');
    }

    public function confirmation(){
        return $this->registryPage('registry/confirmation', 'Confirmation of Appointment', 'confirmation', [
            'confirmations' => Confirmation::with('staff')->latest('appointment_date')->paginate(self::PER_PAGE)->withQueryString(),
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

        // The staff record carries the plain fact of when the appointment was
        // confirmed, so a confirmed exercise writes its date there too.
        if ($data['status'] === 'confirmed' && filled($data['confirmation_date'] ?? null)) {
            BasicInformation::where('ulid', $data['basic_information_ulid'])
                ->update(['date_of_confirmation' => $data['confirmation_date']]);
        }

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
            'queries' => StaffQuery::with('staff')->latest('query_date')->paginate(self::PER_PAGE)->withQueryString(),
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

    public function record_amendments(){
        return $this->registryPage('registry/record-amendments', 'Record Amendments', 'record-amendments', [
            'amendments' => RecordAmendment::with('staff')->latest()->paginate(self::PER_PAGE)->withQueryString(),
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

        // Origin fields are foreign keys, so the requested name has to name a
        // real state or LGA. Catch it here rather than at approval time.
        if (in_array($data['field'], ['state_of_origin', 'lga_of_origin'], true)
            && $this->resolveOriginValue($data['field'], $data['requested_value'], $data['basic_information_ulid']) === null) {

            $label = $data['field'] === 'state_of_origin' ? 'state' : 'LGA';

            return back()->withInput()->withErrors([
                'requested_value' => "No {$label} on record is named \"{$data['requested_value']}\"."
                    . ($label === 'LGA' ? ' An LGA must belong to the officer\'s state of origin.' : ''),
            ]);
        }

        RecordAmendment::create($data + ['status' => 'pending']);

        return redirect()->route('record-amendments')->with('status', 'Amendment request submitted for review.');
    }

    /**
     * Translate an amendment's requested state or LGA name into the id the
     * column actually holds. Returns null when nothing matches, so the caller
     * can reject rather than write a bad key. LGAs resolve within the officer's
     * own state because several states share LGA names.
     */
    private function resolveOriginValue(string $field, string $value, string $staffUlid): ?int
    {
        if ($field === 'state_of_origin') {
            return State::where('name', $value)->value('id');
        }

        $stateId = BasicInformation::where('ulid', $staffUlid)->value('state_of_origin');

        if ($stateId === null) {
            return null;
        }

        return Lga::where('state_id', $stateId)->where('lga_name', $value)->value('id');
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
            $value = $amendment->requested_value;

            if (in_array($amendment->field, ['state_of_origin', 'lga_of_origin'], true)) {
                $value = $this->resolveOriginValue(
                    $amendment->field, $amendment->requested_value, $amendment->basic_information_ulid
                );

                if ($value === null) {
                    return back()->withErrors([
                        'decision' => "\"{$amendment->requested_value}\" no longer matches a known state or LGA, so the record was left untouched.",
                    ]);
                }
            }

            BasicInformation::where('ulid', $amendment->basic_information_ulid)
                ->update([$amendment->field => $value]);
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
