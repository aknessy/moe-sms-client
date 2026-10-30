<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\BenefitPayment;
use App\Models\StaffExit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Support\SpreadsheetExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Everything to do with an officer leaving the service and what is owed to
 * them afterwards: retirement and exit records, the Contributory Pension
 * Scheme, and deceased officers' benefits.
 */
class PensionGratuityController extends Controller
{
    static string $page_title = 'MOE | Staff Management';

    /** How many rows a listing shows per page. */
    private const PER_PAGE = 15;

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    /**
     * Shared payload every page in this section needs, including the staff list
     * that feeds each form's staff picker.
     */
    private function page(string $view, string $title, array $data = [])
    {
        return view($view, array_merge([
            'page_title' => self::$page_title . ' - ' . $title,
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version'),
            'staffOptions' => BasicInformation::query()
                ->orderBy('surname')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->staff_number})"])
                ->all(),
        ], $data));
    }

    public function retirement_exit()
    {
        $today = now()->startOfDay();

        // An officer leaves at 60 or on 35 years of service, whichever reaches
        // them first; the staff record holds both dates and the mode that
        // follows from them.
        $projection = BasicInformation::query()
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function ($s) use ($today) {
                $retiresOn = $s->retiresOn();

                return (object) [
                    'staff' => $s,
                    'date_of_birth' => Carbon::parse($s->date_of_birth)->startOfDay(),
                    'date_of_employment' => $s->date_of_employment,
                    'mode' => $s->mode_of_retirement_label,
                    'retires_on' => $retiresOn,
                    // Signed, so officers already past the mark sort to the top.
                    'months_left' => (int) floor($today->diffInMonths($retiresOn, false)),
                    'time_left' => $this->describeTimeLeft($today, $retiresOn),
                ];
            })
            ->filter(fn ($row) => $row->months_left <= 60)
            ->sortBy('months_left');

        return $this->page('pension/retirement-exit', 'Retirement & Exit', [
            'exits' => StaffExit::with('staff')->latest('effective_date')->paginate(self::PER_PAGE)->withQueryString(),
            'projection' => $projection,
            'benefits' => BenefitPayment::RETIREMENT_BENEFITS,
            'payments' => BenefitPayment::with('staff')
                ->ofType(array_keys(BenefitPayment::RETIREMENT_BENEFITS))
                ->latest('payment_date')
                ->paginate(self::PER_PAGE, ['*'], 'payments')
                ->withQueryString(),
        ]);
    }

    public function store_exit(Request $request)
    {
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'exit_type' => ['required', 'in:retirement,resignation,dismissal,death,transfer-of-service'],
            'effective_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'gratuity_status' => ['required', 'in:pending,processing,paid'],
            'pension_status' => ['required', 'in:pending,processing,paid'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        // Saving the exit sets is_deceased / is_retired on the staff record;
        // see StaffExit::booted().
        StaffExit::create($data);

        return redirect()->route('retirement-exit')->with('status', 'Exit record saved.');
    }

    /**
     * Record money paid out against an exit, with the receipt that evidences
     * it. Which benefits may be chosen depends on the page it was sent from:
     * gratuity and pension on retirement, the death benefits on Deceased.
     */
    public function store_payment(Request $request)
    {
        $allowed = $request->input('context') === 'deceased'
            ? BenefitPayment::DEATH_BENEFITS
            : BenefitPayment::RETIREMENT_BENEFITS;

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'benefit_type' => ['required', Rule::in(array_keys($allowed))],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'paid_to' => ['nullable', 'string', 'max:160'],
            // Evidence of payment. Kept optional because the voucher is often
            // raised before the signed receipt comes back.
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'benefit_type.in' => 'That benefit cannot be paid from this page.',
            'receipt.mimes' => 'A receipt must be a PDF or an image.',
            'receipt.max' => 'A receipt may be at most 4MB.',
        ]);

        $payment = new BenefitPayment(collect($data)->except('receipt')->all());

        if ($file = $request->file('receipt')) {
            // Private disk: a payment receipt is not something to leave
            // readable to anyone who guesses the URL.
            $payment->receipt_path = $file->store('receipts/benefit-payments');
            $payment->receipt_name = $file->getClientOriginalName();
        }

        $payment->save();

        return redirect()
            ->route($request->input('context') === 'deceased' ? 'deceased' : 'retirement-exit')
            ->with('status', $payment->benefit_label . ' payment recorded'
                . ($payment->has_receipt ? ' with its receipt.' : ' — no receipt attached yet.'));
    }

    /**
     * Hand back a receipt. Behind the dashboard's auth, and read off the
     * private disk rather than exposed as a public URL.
     */
    public function receipt(string $ulid)
    {
        $payment = BenefitPayment::where('ulid', $ulid)->firstOrFail();

        abort_unless($payment->has_receipt && Storage::exists($payment->receipt_path), 404);

        return Storage::download($payment->receipt_path, $payment->receipt_name);
    }

    public function cps()
    {
        return $this->page('pension/cps', 'CPS');
    }

    /**
     * The deceased register as a spreadsheet, honouring whatever year filter
     * is in force. The whole filtered list goes in: a register broken at row
     * fifteen would be no use to whoever asked for it.
     */
    public function deceased_export(Request $request)
    {
        ['exits' => $deathExits, 'year' => $year, 'query' => $query] = $this->deceasedRegister($request);

        $deathExits = $deathExits->keyBy('basic_information_ulid');

        $rows = $query->get()->map(function ($person) use ($deathExits) {
            $exit = $deathExits[$person->ulid] ?? null;

            return [
                $person->staff_number, $person->surname, $person->firstname, $person->othernames,
                // Held as numbers, written out the way the register reads them.
                $person->department, $person->designation,
                $person->grade_level_label, $person->step_label,
                $person->date_of_birth, $exit?->effective_date,
                $person->state?->name, $person->lga?->lga_name,
                $exit ? ucfirst($exit->gratuity_status) : null,
                $exit ? ucfirst($exit->pension_status) : null,
            ];
        });

        return SpreadsheetExport::download(
            SpreadsheetExport::filename('deceased-officers', $year ? (string) $year : 'all-years'),
            $year ? "Deceased {$year}" : 'Deceased',
            ['File / Computer No.', 'Surname', 'First Name', 'Other Names', 'Department',
             'Designation', 'Grade Level', 'Step', 'Date of Birth', 'Date of Death',
             'State of Origin', 'LGA of Origin', 'Gratuity', 'Pension'],
            $rows,
        );
    }

    /**
     * The deceased register, optionally narrowed to one year of death. Death
     * exits are where the date of death is held, so they are what the year
     * filter works from.
     *
     * @return array{exits: \Illuminate\Support\Collection, years: \Illuminate\Support\Collection, year: ?int, query: \Illuminate\Database\Eloquent\Builder}
     */
    private function deceasedRegister(Request $request): array
    {
        $exits = StaffExit::where('exit_type', 'death')
            ->orderByDesc('effective_date')
            ->get();

        $years = $exits
            ->map(fn ($exit) => Carbon::parse($exit->effective_date)->year)
            ->unique()
            ->sortDesc()
            ->values();

        $requested = $request->query('year');
        $year = $years->contains((int) $requested) ? (int) $requested : null;

        $query = BasicInformation::deceased()
            ->with(['state', 'lga'])
            ->when($year !== null, fn ($q) => $q->whereIn(
                'ulid',
                $exits->filter(fn ($e) => Carbon::parse($e->effective_date)->year === $year)
                    ->pluck('basic_information_ulid')
            ))
            ->orderBy('surname');

        return compact('exits', 'years', 'year', 'query');
    }

    public function deceased(Request $request)
    {
        ['exits' => $deathExits, 'years' => $years, 'year' => $year, 'query' => $query]
            = $this->deceasedRegister($request);

        return $this->page('pension/deceased', 'Deceased', [
            'deceased' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'years' => $years,
            'year' => $year,
            // The death exit already on file, where one was recorded, so the
            // listing can show the date without duplicating it onto the staff
            // record.
            'deathExits' => $deathExits->keyBy('basic_information_ulid'),
            'benefits' => BenefitPayment::DEATH_BENEFITS,
            // Only officers on record as deceased can be paid a death benefit.
            'deceasedOptions' => BasicInformation::deceased()
                ->orderBy('surname')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->ulid => trim("{$s->surname} {$s->firstname}") . " ({$s->staff_number})"])
                ->all(),
            'payments' => BenefitPayment::with('staff')
                ->ofType(array_keys(BenefitPayment::DEATH_BENEFITS))
                ->latest('payment_date')
                ->paginate(self::PER_PAGE, ['*'], 'payments')
                ->withQueryString(),
        ]);
    }

    /**
     * Mark an officer as deceased, or reverse the entry when it was recorded
     * against the wrong person.
     */
    public function store_deceased(Request $request)
    {
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'is_deceased' => ['required', 'boolean'],
        ], [
            'basic_information_ulid.required' => 'Choose the officer this record is for.',
        ]);

        $staff = BasicInformation::where('ulid', $data['basic_information_ulid'])->firstOrFail();
        $staff->update(['is_deceased' => $data['is_deceased'] ? 1 : 0]);

        $name = trim("{$staff->surname}, {$staff->firstname}");

        return redirect()->route('deceased')->with('status', $data['is_deceased']
            ? "{$name} recorded as deceased."
            : "{$name} is no longer recorded as deceased.");
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
}
