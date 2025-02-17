<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'basic_information_ulid' => BasicInformation::class,
            'designation' => $this->faker->text(),
            'grade_level' => $this->faker->numberBetween(1, 17),
            'step' => $this->faker->numberBetween(1, 15),
            'effective_from' => $this->faker->date('Y-m'),
        ];
    }
}
