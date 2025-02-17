<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Career>
 */
class CareerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mode_of_employment = [
            'Permanent',
            'Temporary',
            'Contractual',
            'Voluntary',
            'Other'
        ];

        return [
            'basic_information_ulid' => BasicInformation::class,
            'date_of_employment' => $this->faker->date(),
            'rank' => $this->faker->text(),
            'grade_level' => $this->faker->numberBetween(1, 17),
            'ministeral_dept' => $this->faker->numberBetween(1, 100),
            'mode_of_employment' => $this->faker->randomElement($mode_of_employment),
            'address_of_ministerial_dept' => $this->faker->address()
        ];
    }
}
