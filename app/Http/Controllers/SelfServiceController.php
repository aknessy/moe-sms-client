<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\Training;
use App\Models\TrainingInterest;
use App\Models\VoluntaryRetirementRequest;
use App\Support\LeaveLetter;
use App\Support\Menu;
use App\Support\PostingContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * An officer's own corner of the system.
 *
 * Every page here is about the officer signed in and no one else, which is why
 * these are their own routes rather than the Registry's with a filter bolted
 * on: there is no officer to choose, so there is no way to choose the wrong
 * one. Each action reads the staff record off the session and never off the
 * request.
 */
class SelfServiceController extends Controller
{
    static string $page_title = 'MOE | My Records';

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
            'app_version' => config('app.app_version'),
            'staff' => $this->staff(),
        ], $data));
    }

    /**
     * The officer signed in. An account with no staff record behind it has
     * nothing to show on any of these pages, so it is turned away here rather
     * than left to fail a field at a time.
     */
    private function staff(): BasicInformation
    {
        $staff = auth()->user()?->staff;

        abort_if($staff === null, 403,
            'This account is not linked to a staff record, so there is nothing of yours to show.');

        return $staff;
    }

    /* ------------------------------------------------ performance appraisal */

    /**
     * Their landing page, and the nearest thing an officer has to a dashboard:
     * where they stand, where they are posted, and when they fall due to go.
     */
    public function appraisal()
    {
        $staff = $this->staff();

        return $this->page('self-service/appraisal', 'Performance Appraisal', 'my-appraisal', [
            'posting' => PostingContext::for($staff),
            'retiresOn' => $staff->retiresOn(),
            // Still to be worked out: who assesses an officer, and against
            // what, is not settled yet.
            'appraisal' => null,
        ]);
    }

    /* ------------------------------------------------------ leave */

    public function leave()
    {
        $staff = $this->staff();

        return $this->page('self-service/leave', 'Leave Management', 'my-leave', [
            'applications' => $staff->leaveApplications()->newestFirst()->paginate(15),
            'leaveTypes' => LeaveApplication::TYPES,
            // Readonly on the form: it follows from their grade level.
            'days' => $staff->leaveDays(),
            // An administrator is an officer too: they apply for their own
            // leave on this page like anyone else, and the table above is
            // theirs. Everyone else's goes in its own table below, so the two
            // are never confused for each other.
            'others' => Menu::isSelfService(auth()->user())
                ? null
                : LeaveApplication::with('staff')
                    ->where('basic_information_ulid', '!=', $staff->ulid)
                    ->newestFirst()
                    ->paginate(15, ['*'], 'others'),
        ]);
    }

    public function store_leave(Request $request)
    {
        $staff = $this->staff();

        $annual = LeaveApplication::entitlementApplies($request->input('leave_type'));

        $data = $request->validate([
            'leave_type' => ['required', Rule::in(array_keys(LeaveApplication::TYPES))],
            'effective_date' => ['required', 'date'],
            // Annual leave runs for as long as the grade level allows, so it
            // is not asked for. Every other kind runs for as long as the case
            // calls for, so it is.
            'days' => ['nullable', Rule::requiredIf(! $annual), 'integer', 'min:1', 'max:365'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'leave_type.required' => 'Choose the kind of leave you are applying for.',
            'effective_date.required' => 'Say which day the leave should start.',
            'days.required' => 'Say how many days of leave you are applying for.',
            'days.min' => 'A leave runs for at least one day.',
        ]);

        $days = $annual ? $staff->leaveDays() : (int) $data['days'];

        if ($days === null) {
            return back()->withInput()->withErrors([
                'leave_type' => 'There is no grade level on your record, so your annual leave '
                    . 'entitlement cannot be worked out. Ask the Registry to correct it, '
                    . 'or apply for another kind of leave.',
            ]);
        }

        // Whose application it is comes from the session, not the form. For
        // annual leave the day count comes from the grade level, and what was
        // submitted is ignored.
        LeaveApplication::create(array_merge($data, [
            'basic_information_ulid' => $staff->ulid,
            'status' => 'pending',
            // Overrides what was submitted, which is what makes the field
            // readonly for annual leave more than a suggestion.
            'days' => $days,
        ]));

        return redirect()->route('my-leave')
            ->with('status', 'Your leave application has been submitted, and is awaiting a decision.');
    }

    /** Their own application, set out as the letter it would be on paper. */
    public function show_leave(string $ulid)
    {
        $staff = $this->staff();

        // Their own, and only their own: the ulid is not a way into anyone
        // else's record.
        $application = LeaveApplication::with('staff')
            ->where('ulid', $ulid)
            ->where('basic_information_ulid', $staff->ulid)
            ->firstOrFail();

        return $this->page('self-service/leave-letter', 'Leave Application', 'my-leave', [
            'letter' => LeaveLetter::for($application),
            'application' => $application,
        ]);
    }

    /* --------------------------------------------------------- training */

    public function training()
    {
        $staff = $this->staff();

        return $this->page('self-service/training', 'Training', 'my-training', [
            'trainings' => Training::with('interests')->soonestFirst()->get(),
        ]);
    }

    /**
     * Put their name down for a training, or take it off again. Interest is
     * not a nomination — it says they would like to go.
     */
    public function indicate_interest(Request $request)
    {
        $staff = $this->staff();

        $data = $request->validate([
            'training_ulid' => ['required', 'exists:trainings,ulid'],
        ]);

        $training = Training::where('ulid', $data['training_ulid'])->firstOrFail();

        if (! $training->is_open) {
            return back()->withErrors([
                'training_ulid' => 'That training is no longer taking names.',
            ]);
        }

        $existing = TrainingInterest::where('training_id', $training->id)
            ->where('basic_information_ulid', $staff->ulid)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return back()->with('status', "Your name has been taken off {$training->title}.");
        }

        TrainingInterest::create([
            'training_id' => $training->id,
            'basic_information_ulid' => $staff->ulid,
        ]);

        return back()->with('status', "Your interest in {$training->title} has been noted.");
    }

    /* --------------------------------------------------------- accounts */

    public function payslip()
    {
        return $this->page('self-service/payslip', 'Payslip', 'my-payslip');
    }

    public function deductions()
    {
        return $this->page('self-service/deductions', 'Deductions', 'my-deductions');
    }

    /* ---------------------------------------------------------- profile */

    public function bioData()
    {
        $staff = $this->staff();

        return $this->page('self-service/bio-data', 'Bio-data', 'my-bio-data', [
            'posting' => PostingContext::for($staff),
        ]);
    }

    public function photo()
    {
        return $this->page('self-service/photo', 'Update Photo', 'my-photo');
    }

    public function store_photo(Request $request)
    {
        $staff = $this->staff();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'photo.required' => 'Choose the photograph you want to upload.',
            'photo.image' => 'A passport photograph must be an image.',
            'photo.mimes' => 'A photograph must be a JPEG or a PNG.',
            'photo.max' => 'A photograph may be at most 2MB.',
        ]);

        $previous = $staff->photo_path;

        // Private disk: a staff photograph is not something to leave sitting
        // in the public folder.
        $staff->photo_path = $request->file('photo')->store('photos/staff');
        $staff->photo_name = $request->file('photo')->getClientOriginalName();
        $staff->save();

        // The one it replaced is of no further use.
        if (filled($previous) && $previous !== $staff->photo_path) {
            Storage::delete($previous);
        }

        return redirect()->route('my-photo')->with('status', 'Your photograph has been updated.');
    }

    /** Hand back a photograph, from behind the dashboard's auth. */
    public function show_photo()
    {
        $staff = $this->staff();

        abort_unless($staff->has_photo && Storage::exists($staff->photo_path), 404);

        return Storage::response($staff->photo_path);
    }

    /* ---------------------------------------------- voluntary retirement */

    /**
     * May this account raise a request on another officer's behalf? An
     * officer applies for themselves and no one else; anyone with the run of
     * the system may do it for whoever walks in with the paperwork.
     */
    private function mayApplyForOthers(): bool
    {
        return ! Menu::isSelfService(auth()->user());
    }

    public function retirement()
    {
        $staff = $this->staff();
        $forOthers = $this->mayApplyForOthers();

        return $this->page('self-service/retirement', 'Voluntary Retirement', 'my-retirement', [
            'requests' => $staff->voluntaryRetirementRequests()->newestFirst()->get(),
            'pending' => $staff->voluntaryRetirementRequests()->pending()->exists(),
            'retiresOn' => $staff->retiresOn(),
            'forOthers' => $forOthers,
            // The officers a request may be raised for, and when each of them
            // falls due anyway, so picking one says what it is they are asking
            // to bring forward.
            'staffOptions' => $forOthers ? $this->activeStaffOptions() : [],
            'dueDates' => $forOthers ? $this->retirementDueDates() : [],
            // Everyone else's requests, so whoever handles them sees what is
            // waiting without hunting for it.
            'others' => $forOthers
                ? VoluntaryRetirementRequest::with('staff')
                    ->where('basic_information_ulid', '!=', $staff->ulid)
                    ->newestFirst()
                    ->get()
                : null,
        ]);
    }

    public function store_retirement(Request $request)
    {
        // Whose request this is: their own unless they may raise one for
        // someone else and have named them.
        $applicant = $this->applicantFor($request);

        if ($applicant instanceof \Illuminate\Http\RedirectResponse) {
            return $applicant;
        }

        $ownRequest = $applicant->ulid === $this->staff()->ulid;

        // One request at a time: a second would only be the same question
        // asked twice while the first is still being considered.
        if ($applicant->voluntaryRetirementRequests()->pending()->exists()) {
            return back()->withInput()->withErrors([
                'intended_date' => $ownRequest
                    ? 'You already have a request awaiting a decision.'
                    : 'That officer already has a request awaiting a decision.',
            ]);
        }

        $data = $request->validate([
            // Leaving early means naming a day still to come.
            'intended_date' => ['required', 'date', 'after:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'intended_date.required' => 'Say which day the officer intends to leave the service.',
            'intended_date.after' => 'The day of leaving must be a day still to come.',
            'reason.required' => 'Give the reason for applying to retire early.',
        ]);

        VoluntaryRetirementRequest::create($data + [
            'basic_information_ulid' => $applicant->ulid,
            'status' => 'pending',
        ]);

        return redirect()->route('my-retirement')->with('status', $ownRequest
            ? 'Your application for voluntary retirement has been submitted.'
            : 'The application for voluntary retirement has been submitted.');
    }

    /**
     * The officer a request is being raised for. Their own unless this account
     * may raise one for someone else and has named them — an officer cannot
     * put another officer out of the service by editing the form.
     */
    private function applicantFor(Request $request): BasicInformation|\Illuminate\Http\RedirectResponse
    {
        $staff = $this->staff();
        $named = $request->input('basic_information_ulid');

        if (! $this->mayApplyForOthers() || blank($named) || $named === $staff->ulid) {
            return $staff;
        }

        $applicant = BasicInformation::query()
            ->inActiveService()
            ->where('ulid', $named)
            ->first();

        if ($applicant === null) {
            return back()->withInput()->withErrors([
                'basic_information_ulid' => 'That officer is not in active service.',
            ]);
        }

        return $applicant;
    }

    /** Officers a request may be raised for, as the picker lists them. */
    private function activeStaffOptions()
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

    /** When each officer falls due to leave anyway, and by which route. */
    private function retirementDueDates()
    {
        return BasicInformation::query()
            ->inActiveService()
            ->get()
            ->mapWithKeys(fn ($s) => [$s->ulid => [
                'on' => $s->retiresOn()?->toDateString(),
                'mode' => $s->mode_of_retirement_label,
            ]]);
    }
}
