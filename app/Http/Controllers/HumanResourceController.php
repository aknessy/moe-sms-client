<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
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
        return $this->page('human-resources/leave-management', 'Leave Management', 'leave-management', [
            // Newest first: the applications waiting to be dealt with are the
            // ones just made.
            'applications' => LeaveApplication::with('staff')
                ->newestFirst()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
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
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'leave_type' => ['required', Rule::in(array_keys(LeaveApplication::TYPES))],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'basic_information_ulid.required' => 'Choose the officer applying for leave.',
            'basic_information_ulid.exists' => 'That officer is not on the roll.',
            'leave_type.required' => 'Choose the kind of leave.',
        ]);

        // The day count is the officer's entitlement, which follows from their
        // grade level. It is shown readonly and taken from the record, not
        // from the form.
        $staff = BasicInformation::where('ulid', $data['basic_information_ulid'])->first();
        $days = $staff?->leaveDays();

        if ($days === null) {
            return back()->withInput()->withErrors([
                'basic_information_ulid' => 'That officer has no grade level on record, '
                    . 'so there is nothing to work their leave entitlement out from.',
            ]);
        }

        // The resumption date follows from this and the effective date; see
        // the model.
        LeaveApplication::create($data + ['days' => $days]);

        return redirect()->route('hr-leave')->with('status', 'Leave application recorded.');
    }

    public function promotions()
    {
        return $this->page('human-resources/promotion-exercises', 'Promotion Exercises', 'promotion-exercises');
    }

    public function appraisal()
    {
        return $this->page('human-resources/performance-appraisal', 'Performance Appraisal', 'performance-appraisal');
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
