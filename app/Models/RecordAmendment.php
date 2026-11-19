<?php

namespace App\Models;

use App\Support\ApplicationRouting;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class RecordAmendment extends Model
{
    use HasUlids;

    protected $table = 'record_amendments';

    protected $fillable = ['basic_information_ulid', 'field', 'current_value', 'requested_value', 'reason', 'supporting_document', 'status', 'reviewed_by', 'reviewed_at'];

    /**
     * The fields a correction may be raised against, and what to call each
     * one — shared between the Registry's own form and the one an officer
     * raises against their own record, so the two never drift apart.
     */
    public const AMENDABLE_FIELDS = [
        'file_number' => 'File Number',
        'computer_number' => 'Establishment/Computer Number',
        'surname' => 'Surname',
        'firstname' => 'First Name',
        'othernames' => 'Other Names',
        'department' => 'Department',
        'division' => 'Division / Unit',
        'designation' => 'Designation',
        'grade_level' => 'Grade Level',
        'step' => 'Step',
        'date_of_birth' => 'Date of Birth',
        'phone_number' => 'Phone Number',
        'email' => 'Email',
        'state_of_origin' => 'State of Origin',
        'lga_of_origin' => 'LGA of Origin',
        'address' => 'Address',
        'spouse_name' => 'Spouse',
        'next_of_kin' => 'Next of Kin',
        'referee' => 'Referee',
    ];

    /**
     * Fields with no single column to write a value into: a next of kin or a
     * referee is a row of its own, not a cell on the staff record, and an
     * officer may hold several. Approving one of these marks the request
     * reviewed but writes nothing — the Registry applies the change itself,
     * through Family & Referees, which is where the notice sends them.
     */
    public const INDIRECT_FIELDS = ['next_of_kin', 'referee'];

    /** The one role that may decide an amendment request, direct or not. */
    public const APPROVING_ROLE = 'HOD GAP';

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    public function getIsIndirectAttribute(): bool
    {
        return in_array($this->field, self::INDIRECT_FIELDS, true);
    }

    /** Whether this account may approve or reject an amendment request. */
    public static function mayApprove(?User $viewer): bool
    {
        return $viewer !== null && $viewer->role === self::APPROVING_ROLE;
    }

    protected static function booted(): void
    {
        // Whoever has to act on this — decide it, or correct a next of kin or
        // referee by hand — is told it exists, the same way a leave
        // application tells its reviewers, rather than left for someone to
        // happen upon in the list.
        static::created(fn (self $amendment) => $amendment->notifyReviewers());
    }

    /**
     * Tell HOD GAP, who decides it, and the administrators and Registry
     * officers who see everything, that a correction is waiting.
     */
    public function notifyReviewers(): int
    {
        $staff = $this->staff;

        if ($staff === null) {
            return 0;
        }

        $name = trim($staff->surname . ', ' . trim($staff->firstname . ' ' . $staff->othernames));
        $label = self::AMENDABLE_FIELDS[$this->field] ?? str_replace('_', ' ', $this->field);

        return Notification::send(
            $this->recipients($staff),
            "Amendment request from {$name}",
            "Requesting a correction to {$label}: \"{$this->requested_value}\"."
                . ($this->is_indirect ? ' This is held on its own record, not a single field — open Family & Referees to make the change.' : ' Awaiting HOD GAP\'s decision.'),
            $this->is_indirect
                ? route('family-referees', ['staff' => $staff->ulid])
                : route('record-amendments'),
        );
    }

    /**
     * Tell the officer whose record this is that their request has been
     * decided — written onto their record already, for a direct field, or
     * forwarded for the Registry to apply by hand, for a next of kin or
     * referee.
     */
    public function notifyApplicant(): void
    {
        $applicant = User::where('basic_information_ulid', $this->basic_information_ulid)->first();

        if ($applicant === null) {
            return;
        }

        $label = self::AMENDABLE_FIELDS[$this->field] ?? str_replace('_', ' ', $this->field);

        Notification::send(
            [$applicant],
            "Your {$label} correction was approved",
            $this->is_indirect
                ? "Approved — the Registry will apply it to your {$label} shortly."
                : "Approved and written onto your record: {$this->requested_value}.",
            route('my-bio-data'),
        );
    }

    /** @return Collection<User> */
    private function recipients(BasicInformation $staff): Collection
    {
        return User::query()
            ->where('is_active', 1)
            ->whereHas('roleRecord', fn ($q) => $q->whereIn('role', [
                ApplicationRouting::ADMIN_ROLE, 'Registry Officer', self::APPROVING_ROLE,
            ]))
            ->get()
            // The officer raising this against their own record needs no
            // telling — they already know what they just asked for.
            ->reject(fn (User $user) => $user->basic_information_ulid === $staff->ulid)
            ->unique('id')
            ->values();
    }
}