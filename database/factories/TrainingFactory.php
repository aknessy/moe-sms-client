<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Training>
 */
class TrainingFactory extends Factory
{
    public function definition(): array
    {
        $starts = $this->faker->dateTimeBetween('+1 week', '+6 months');

        return [
            'title' => $this->faker->randomElement([
                'Electoral Officers Refresher Course',
                'Records Management Workshop',
                'Public Procurement Training',
                'Leadership & Supervisory Skills',
                'Voter Register Data Handling',
            ]),
            'description' => $this->faker->sentence(12),
            'venue' => $this->faker->randomElement(['Calabar', 'Abuja', 'Lagos', 'Enugu']),
            'starts_on' => $starts->format('Y-m-d'),
            'ends_on' => (clone $starts)->modify('+4 days')->format('Y-m-d'),
            'is_open' => true,
        ];
    }

    /** No longer taking names. */
    public function closed(): static
    {
        return $this->state(fn () => ['is_open' => false]);
    }
}
