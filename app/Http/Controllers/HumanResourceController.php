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
        ]);
    }

    public function store_leave_application(Request $request)
    {
        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
            'leave_type' => ['required', Rule::in(array_keys(LeaveApplication::TYPES))],
            'effective_date' => ['required', 'date'],
            // A leave of no days is not a leave.
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'basic_information_ulid.required' => 'Choose the officer applying for leave.',
            'basic_information_ulid.exists' => 'That officer is not on the roll.',
            'days.min' => 'A leave runs for at least one day.',
            'days.required' => 'Say how many days the leave runs for.',
            'leave_type.required' => 'Choose the kind of leave.',
        ]);

        // Worked out from the effective date and the days; see the model.
        LeaveApplication::create($data);

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
