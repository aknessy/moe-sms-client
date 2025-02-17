<?php

namespace Database\Factories;

use App\Models\BasicInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class NextOfKinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $relationships = [
            'Brother',
            'Sister',
            'Father',
            'Mother',
            'Wife',
            'Husband',
            'Son',
            'Daughter',
        ];

        return [
            'basic_information_ulid' => BasicInformation::class,
            'nok_name' => $this->faker->name(),
            'nok_relationship' => $this->faker->randomElement($relationships),
            'nok_address' => $this->faker->address(),
            'nok_phone_number' => $this->faker->phoneNumber(),
        ];
    }
}
