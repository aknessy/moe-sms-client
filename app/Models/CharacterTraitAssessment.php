<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Character Traits section of an officer's appraisal: a letter grade for
 * each of dependability, appearance, attendance and resource utilization, and
 * the reporting officer's own words for why — covering all four together.
 */
class CharacterTraitAssessment extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'dependability_grade', 'appearance_grade',
        'attendance_grade', 'resource_utilization_grade',
        'justification',
    ];

    /** The grades a reporting officer chooses between, for each trait. */
    public const GRADES = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent', 'Outstanding'];

    /** Column => how it reads, and what it asks the reporting officer to weigh. */
    public const TRAITS = [
        'dependability_grade' => [
            'label' => 'Dependability',
            'description' => 'Whether the officer is able to work consistently '
                . 'without close supervision, inspection or compulsion.',
        ],
        'appearance_grade' => [
            'label' => 'Appearance',
            'description' => 'How presentable the officer is.',
        ],
        'attendance_grade' => [
            'label' => 'Attendance',
            'description' => 'Whether the officer comes to work consistently.',
        ],
        'resource_utilization_grade' => [
            'label' => 'Resource Utilization',
            'description' => 'How the officer makes use of what the service '
                . 'gives them to work with.',
        ],
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
