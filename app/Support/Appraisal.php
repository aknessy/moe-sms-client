<?php

namespace App\Support;

/**
 * The sections an Annual Performance Evaluation Report is made of.
 *
 * Held as data rather than as thirteen routes, the way the staff wizard does
 * it: these are parts of one report on one officer, not separate records, so
 * one route carries the section as a parameter and the rail reads off here.
 */
class Appraisal
{
    /** Slug => how it reads on the rail, in the order it is worked through. */
    public const SECTIONS = [
        'bio-data' => 'Bio-data',
        'career-history' => 'Career History',
        'qualifications' => 'Qualifications',
        'self-reporting' => 'Self Reporting',
        'character-traits' => 'Character Traits',
        'commendations' => 'Commendations',
        'sanctions' => 'Sanctions',
        'leadership-traits' => 'Leadership Traits',
        'general-remarks' => 'General Remarks',
        'suggestions' => 'Suggestions (By Superior Officer)',
        'staff-declaration' => 'Staff Declaration',
        'reporting-officer-declaration' => 'Reporting Officer Declaration',
        'counter-signing-officer-report' => "Counter Signing Officer's Report",
    ];

    /**
     * The kinds of qualification an appraisal asks after. "Tertiary" is what
     * the staff wizard already records against an institution, so it stays
     * among them rather than being renamed out from under the data.
     */
    public const QUALIFICATION_TYPES = [
        'Educational',
        'Professional',
        'Technical',
        'Tertiary',
    ];

    /** The one a report opens on. */
    public static function firstSection(): string
    {
        return array_key_first(self::SECTIONS);
    }

    public static function has(?string $section): bool
    {
        return $section !== null && array_key_exists($section, self::SECTIONS);
    }

    /** The section asked for, or the first where none was named or it is not one. */
    public static function resolve(?string $section): string
    {
        return self::has($section) ? $section : self::firstSection();
    }

    public static function label(?string $section): string
    {
        return self::SECTIONS[self::resolve($section)];
    }

    /** Which number of how many, for the rail's progress. */
    public static function position(?string $section): int
    {
        return array_search(self::resolve($section), array_keys(self::SECTIONS), true) + 1;
    }

    public static function count(): int
    {
        return count(self::SECTIONS);
    }

    /**
     * Sections that take a form of their own, and so carry their own save
     * and their own way on to the next. The rest are still to be built.
     */
    public const WITH_FORMS = ['bio-data', 'career-history', 'qualifications',
                               'self-reporting', 'character-traits', 'commendations',
                               'sanctions'];

    public static function hasForm(?string $section): bool
    {
        return in_array(self::resolve($section), self::WITH_FORMS, true);
    }

    /** The section after this one, or null at the end of the report. */
    public static function next(?string $section): ?string
    {
        $slugs = array_keys(self::SECTIONS);
        $at = array_search(self::resolve($section), $slugs, true);

        return $slugs[$at + 1] ?? null;
    }

    /** "Continue to Career History", or null at the end of the report. */
    public static function continueLabel(?string $section): ?string
    {
        $next = self::next($section);

        return $next === null ? null : 'Continue to ' . self::SECTIONS[$next];
    }
}
