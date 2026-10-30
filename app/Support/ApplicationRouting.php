<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who an application goes through, and who should hear it has been made.
 *
 * An application travels up a chain: the officer writes to the Commissioner,
 * through the Administrative Secretary, through whoever they answer to day to
 * day. That last one is either the Electoral Officer of the LGA they are
 * posted to, or the head of the department they sit in at headquarters — which
 * is decided here, in one place, so the letter and the notification cannot
 * name different people.
 */
class ApplicationRouting
{
    /**
     * The rank an LGA's Electoral Officer holds. Matched exactly: "Senior
     * Electoral Officer" and "Assistant Chief Electoral Officer" are ranks,
     * not the officer in charge of a local government.
     */
    public const ELECTORAL_OFFICER = 'Electoral Officer';

    /** The role that signs off on the way to the Commissioner. */
    public const SECRETARY_ROLE = 'Administrative Secretary';

    /** Accounts that see everything, and so see every application. */
    public const ADMIN_ROLE = 'Administrator';

    /**
     * The officer this application goes through first.
     *
     * Posted away from headquarters, that is the Electoral Officer of the LGA
     * they are in — they are the one who knows whether the officer can be
     * spared. At headquarters there is no such post, so it is the head of
     * their department.
     *
     * @return array{title: string, user: ?User, staff: ?BasicInformation}
     */
    public static function immediateSuperior(BasicInformation $staff): array
    {
        $posting = PostingContext::for($staff);
        $headquarters = PostingContext::default();

        $atHeadquarters = $posting['lga_id'] !== null
            && $posting['lga_id'] === $headquarters['lga_id'];

        if (! $atHeadquarters && filled($posting['lga'])) {
            $officer = self::electoralOfficerOf($posting['lga_id'], $staff);

            return [
                'title' => "The Electoral Officer, {$posting['lga']} LGA",
                'user' => $officer?->user,
                'staff' => $officer,
            ];
        }

        return self::headOfDepartment($staff);
    }

    /**
     * The desk a department's head sits at, named so it does not stutter.
     *
     * Departments are recorded in full — "Department of Electoral Operations"
     * — so the usual "The Head of Department, X" would read "The Head of
     * Department, Department of Electoral Operations". Where the name already
     * says what it is, the title does not repeat it.
     */
    public static function headTitleFor(?string $department): string
    {
        if (blank($department)) {
            return 'The Head of Department';
        }

        return str_starts_with(strtolower($department), 'department of')
            ? "The Head, {$department}"
            : "The Head of Department, {$department}";
    }

    /** The head of this officer's department, where one has been named. */
    public static function headOfDepartment(BasicInformation $staff): array
    {
        $role = DepartmentHead::roleFor($staff->department);

        $title = self::headTitleFor($staff->department);

        // No role set against the department means nobody has said who heads
        // it. The letter still names the desk; only the notice has nowhere to
        // go, and falls back to the administrators.
        $head = $role === null ? null : self::holderOf($role, $staff);

        return ['title' => $title, 'user' => $head, 'staff' => $head?->staff];
    }

    /**
     * The chain a letter is addressed through, nearest desk last — the order
     * it is written in: to the Commissioner, through the Secretary, through
     * the officer's own superior.
     *
     * @return array{addressee: string, through: array<string>}
     */
    public static function chainFor(BasicInformation $staff): array
    {
        return [
            'addressee' => \App\Models\Setting::get('organisation.commissioner_title'),
            'through' => [
                'The Administrative Secretary',
                self::immediateSuperior($staff)['title'],
            ],
        ];
    }

    /**
     * Everyone who should be told an application has been made: the officer's
     * own superior, and the administrators, who see everything.
     *
     * The applicant is never among them — nobody needs telling what they just
     * did themselves.
     *
     * @return Collection<User>
     */
    public static function recipientsFor(BasicInformation $staff): Collection
    {
        $superior = self::immediateSuperior($staff)['user'];

        return collect([$superior])
            ->concat(self::holdersOf(self::ADMIN_ROLE))
            ->filter()
            // An account only sitting on the applicant's own record would be
            // the applicant.
            ->reject(fn (User $user) => $user->basic_information_ulid === $staff->ulid)
            ->unique('id')
            ->values();
    }

    /**
     * The Electoral Officer posted to an LGA. Where several hold the rank, the
     * first by surname — there should only be one.
     */
    private static function electoralOfficerOf(?int $lgaId, BasicInformation $applicant): ?BasicInformation
    {
        if ($lgaId === null) {
            return null;
        }

        $postings = PostingContext::all();

        return BasicInformation::query()
            ->inActiveService()
            ->where('designation', self::ELECTORAL_OFFICER)
            ->where('ulid', '!=', $applicant->ulid)
            ->orderBy('surname')
            ->with('user')
            ->get()
            ->first(fn ($officer) => ($postings[$officer->ulid]['lga_id'] ?? null) === $lgaId);
    }

    /** The account holding a role, nearest to the applicant where several do. */
    private static function holderOf(string $role, BasicInformation $applicant): ?User
    {
        $holders = self::holdersOf($role)
            ->reject(fn (User $user) => $user->basic_information_ulid === $applicant->ulid);

        // Someone in the applicant's own department first, so a system with
        // more than one holder still picks the one who knows them.
        return $holders->first(fn (User $user) => filled($applicant->department)
                && $user->staff?->department === $applicant->department)
            ?? $holders->first();
    }

    /** @return Collection<User> */
    private static function holdersOf(string $role): Collection
    {
        return User::query()
            ->where('is_active', 1)
            ->whereHas('roleRecord', fn ($q) => $q->where('role', $role))
            ->with('staff')
            ->orderBy('username')
            ->get();
    }
}
