<?php

namespace App\Http\Controllers;

use App\Models\BasicInformation;
use App\Models\GapDelegate;
use App\Models\Notification;
use App\Models\Posting;
use App\Models\User;
use App\Support\GapDesk;
use App\Support\ReportingAuthority;
use Illuminate\Http\Request;

/**
 * HOD GAP designates the officers of their department — or posted to it — as
 * Registry Officers. A designated officer works alongside the head but
 * signs and approves nothing on their behalf.
 */
class GapDelegateController extends Controller
{
    private function requireHead(): User
    {
        $viewer = auth()->user();

        abort_unless(ReportingAuthority::isHodGap($viewer), 403,
            'Only HOD GAP designates Registry Officers.');

        return $viewer;
    }

    /** The department the head answers for: the one on their own staff record. */
    private function department(User $head): ?string
    {
        return GapDesk::department($head);
    }

    private function belongsTo(BasicInformation $staff, ?string $department): bool
    {
        return GapDesk::includes($staff, $department);
    }

    /** Officers of the department, or posted to it, who have an account. */
    private function candidates(User $head)
    {
        $department = $this->department($head);

        return BasicInformation::query()
            ->inActiveService()
            ->where('ulid', '!=', $head->basic_information_ulid)
            ->orderBy('surname')
            ->get()
            ->filter(fn ($staff) => $this->belongsTo($staff, $department))
            ->filter(fn ($staff) => User::where('basic_information_ulid', $staff->ulid)->exists());
    }

    public function index(Request $request)
    {
        $head = $this->requireHead();
        $delegates = GapDelegate::with('staff')->get();
        $delegateUlids = $delegates->pluck('basic_information_ulid');

        return view('gap-delegates', [
            'page_title' => 'MOE | Staff Management - Registry Officer Delegates',
            'active_page' => 'gap-delegates',
            'uri_segments' => explode('/', $request->getRequestUri()),
            'app_version' => config('app.app_version'),
            'department' => $this->department($head),
            'delegates' => $delegates,
            'staffOptions' => $this->candidates($head)
                ->reject(fn ($staff) => $delegateUlids->contains($staff->ulid))
                ->mapWithKeys(fn ($s) => [
                    $s->ulid => trim("{$s->surname}, {$s->firstname} {$s->othernames}")
                        . " ({$s->staff_number})",
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $head = $this->requireHead();

        $data = $request->validate([
            'basic_information_ulid' => ['required', 'exists:basic_informations,ulid'],
        ], [
            'basic_information_ulid.required' => 'Pick a staff.',
        ]);

        $staff = BasicInformation::where('ulid', $data['basic_information_ulid'])->firstOrFail();
        $account = User::where('basic_information_ulid', $staff->ulid)->first();

        if (! $this->belongsTo($staff, $this->department($head))) {
            return back()->withErrors([
                'basic_information_ulid' => 'That officer is not in your department, or posted to it.',
            ]);
        }

        if ($account === null) {
            return back()->withErrors([
                'basic_information_ulid' => 'That officer has no account to designate.',
            ]);
        }

        if (GapDelegate::where('basic_information_ulid', $staff->ulid)->exists()) {
            return back()->withErrors(['basic_information_ulid' => 'That officer is already designated.']);
        }

        // Only an ordinary staff account is moved: someone who already holds
        // another office is not demoted by being named here.
        if ($account->actual_role !== ReportingAuthority::STAFF_ROLE) {
            return back()->withErrors([
                'basic_information_ulid' => "That officer already holds the {$account->actual_role} role.",
            ]);
        }

        GapDelegate::create([
            'basic_information_ulid' => $staff->ulid,
            'designated_by' => $head->basic_information_ulid,
            'previous_role_id' => $account->role_id,
        ]);

        $account->role = GapDelegate::ROLE;
        $account->save();

        Notification::send(
            [$account],
            'You were designated a Registry Officer',
            'HOD GAP designated you a Registry Officer. You work alongside them, but do not sign or approve on their behalf.',
            route('dashboard'),
        );

        return redirect()->route('gap-delegates')
            ->with('status', "{$staff->surname} {$staff->firstname} is now a Registry Officer.");
    }

    public function revoke(string $ulid)
    {
        $this->requireHead();

        $delegate = GapDelegate::where('basic_information_ulid', $ulid)->firstOrFail();
        $account = User::where('basic_information_ulid', $ulid)->first();

        // Back to the role they held before, not to a guess at one.
        if ($account !== null && $account->actual_role === GapDelegate::ROLE) {
            $account->role_id = $delegate->previous_role_id;
            $account->save();
        }

        $delegate->delete();

        return redirect()->route('gap-delegates')->with('status', 'Designation withdrawn.');
    }
}
