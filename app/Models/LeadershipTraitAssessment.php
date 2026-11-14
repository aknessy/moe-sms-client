<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Leadership Traits section of an officer's appraisal: a letter grade
 * for each of five questions on how the officer leads, and one more for the
 * overall assessment the five of them add up to.
 */
class LeadershipTraitAssessment extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'standards_grade', 'timeliness_grade', 'example_grade',
        'suggestions_grade', 'value_added_grade', 'overall_grade',
    ];

    /** The grades a reporting officer chooses between, for each question. */
    public const GRADES = ['A', 'B', 'C', 'D', 'E', 'F'];

    /** Column => what it asks the reporting officer to weigh. */
    public const TRAITS = [
        'standards_grade' => [
            'label' => '(i)',
            'description' => 'Does he/she encourage his/her subordinate to define '
                . 'agreed standards and measures for effectiveness before hand?',
        ],
        'timeliness_grade' => [
            'label' => '(ii)',
            'description' => 'Does he/she encourage and train his/her subordinates '
                . 'to avoid late attainment of goals?',
        ],
        'example_grade' => [
            'label' => '(iii)',
            'description' => 'Does he/she show example in terms of punctuality, '
                . 'efficiency and high degree of responsibility in the tasks '
                . 'he/she partakes in?',
        ],
        'suggestions_grade' => [
            'label' => '(iv)',
            'description' => 'Did he/she make suggestions for changes/adjust '
                . 'methods/procedures that significantly contribute to his own '
                . 'work or that of any associates/subordinates?',
        ],
        'value_added_grade' => [
            'label' => '(v)',
            'description' => 'Did the suggestion he/she proffered add any value to the task?',
        ],
    ];

    /**
     * What each letter means for the overall assessment, worst to best read
     * the other way around — the form itself runs best to worst.
     */
    public const OVERALL_GRADES = [
        'A' => [
            'label' => 'Outstanding',
            'description' => 'An exceptionally valuable member of the staff; '
                . 'performance is well above the required standard for the job.',
        ],
        'B' => [
            'label' => 'Very Good',
            'description' => 'Displays good all-round level of effectiveness; '
                . 'performance meets requirements in all important tasks.',
        ],
        'C' => [
            'label' => 'Good',
            'description' => 'A competent member of staff; generally achieves the standards required.',
        ],
        'D' => [
            'label' => 'Satisfactory',
            'description' => 'Completes all assignment satisfactorily within agreed date.',
        ],
        'E' => [
            'label' => 'Fair',
            'description' => 'Performance does not always reach the required standard; room for improvement.',
        ],
        'F' => [
            'label' => 'Poor',
            'description' => 'Performance does not meet the required standard.',
        ],
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
