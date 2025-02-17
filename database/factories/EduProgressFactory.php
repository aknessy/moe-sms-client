<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EduProgress>
 */
class EduProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $institution = ['Other', 'Tertiary', 'Secondary', 'Primary'];

        return [
            'basic_information_ulid' => BasicInformation::class,
            'institution_type' => $institution[rand(0, 3)],
            'institution_name' => $this->faker->company(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'qualification' => $this->faker->text(),
        ];
    }
}
