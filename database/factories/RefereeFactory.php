<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Referee>
 */
class RefereeFactory extends Factory
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
            'referee_name' => $this->faker->name(),
            'referee_address' => $this->faker->address(),
            'referee_phone' => $this->faker->phoneNumber(),
            'referee_email' => $this->faker->unique()->safeEmail()
        ];
    }
}
