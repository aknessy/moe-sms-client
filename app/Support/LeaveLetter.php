<?php

namespace App\Support;

use App\Models\LeaveApplication;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * A leave application, set out as the letter it would be on paper.
 *
 * Everything the page needs is worked out here so the Blade only lays it out:
 * the letterhead, where the officer writes from, who it is addressed to and
 * through whom, and what it says.
 */
class LeaveLetter
{
    public function __construct(private LeaveApplication $application)
    {
    }

    public static function for(LeaveApplication $application): self
    {
        return new self($application);
    }

    public function application(): LeaveApplication
    {
        return $this->application;
    }

    /** The command's own details, as they head the page. */
    public function letterhead(): array
    {
        $organisation = Setting::organisation();

        return [
            'name' => $organisation['organisation.name'],
            'command' => $organisation['organisation.command'],
            'address' => $organisation['organisation.address'],
            'telephone' => $organisation['organisation.telephone'],
            'email' => $organisation['organisation.email'],
        ];
    }

    /**
     * Where the officer writes from: the department they sit in, or the local
     * government they are posted to, and the station itself.
     */
    public function origin(): array
    {
        $staff = $this->application->staff;
        $posting = PostingContext::for($staff);

        return array_values(array_filter([
            $staff?->department,
            filled($posting['lga']) ? $posting['lga'] . ' LGA' : null,
            $posting['station'],
            $posting['state'] ? $posting['state'] . ' State' : null,
        ]));
    }

    /** The day the application was made. */
    public function dated(): string
    {
        return ($this->application->created_at ?? now())->format('j F Y');
    }

    /** Addressed to the Commissioner, through the desks between. */
    public function chain(): array
    {
        return ApplicationRouting::chainFor($this->application->staff);
    }

    /** The heading, which is what the letter is about. */
    public function heading(): string
    {
        return 'APPLICATION FOR ' . strtoupper($this->application->leave_type);
    }

    /**
     * The letter itself, a paragraph at a time. The officer's own words go in
     * as their own paragraph; the rest is the application read back as prose,
     * so what is approved and what was applied for are the same thing.
     */
    public function body(): array
    {
        $application = $this->application;

        $from = $this->formatDate($application->effective_date);
        $resume = $this->formatDate($application->resumption_date);
        $days = $application->days;
        $type = strtolower($application->leave_type);

        $paragraphs = [
            "I write to apply for {$days} working days of {$type}, with effect from {$from}.",
        ];

        if (filled($application->reason)) {
            $paragraphs[] = $application->reason;
        }

        $paragraphs[] = "I undertake to resume duty on {$resume}.";
        $paragraphs[] = 'I shall be grateful if my application is granted.';

        return $paragraphs;
    }

    /** Who signs it, and what they are. */
    public function signatory(): array
    {
        $staff = $this->application->staff;

        return [
            'name' => trim(($staff?->surname ?? '') . ', '
                . trim(($staff?->firstname ?? '') . ' ' . ($staff?->othernames ?? ''))),
            'rank' => $staff?->rank,
            'grade' => $staff?->grade_step,
            'staff_number' => $staff?->staff_number,
        ];
    }

    private function formatDate(?string $date): string
    {
        return filled($date) ? Carbon::parse($date)->format('j F Y') : '—';
    }
}
