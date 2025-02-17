<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Leave>
 */
class LeaveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $records = [];
        $leave_type = [
            'Annual leave',
            'Study leave',
            'Maternity leave',
            'Paternity leave',
            'Retirement leave',
            'Medical leave'
        ];

        $leave_record = [
            'leave_type' => $leave_type[rand(0, 5)],
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'duration' => $this->faker->numberBetween(7, 365),
        ];

        $push = array_push($records, $leave_record);

        return [
            'basic_information_ulid' => BasicInformation::class,
            'leave_record' => json_encode($push),
        ];
    }
}
