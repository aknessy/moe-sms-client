<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BasicInformation>
 */
class BasicInformationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = ['male', 'female'];

        return [
            'ulid' => Str::ulid(),
            'computer_number' => $this->faker->unique()->numerify('COMP-#####'),
            'surname' => $this->faker->lastName($gender[rand(0, 1)]),
            'firstname' => $this->faker->firstName($gender[rand(0, 1)]),
            'othernames' => $this->faker->name($gender[rand(0, 1)]),
            'date_of_birth' => $this->faker->date(),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'state_of_origin' => $this->faker->numberBetween(1, 37),
            'lga_of_origin' => $this->faker->numberBetween(1, 774),
            'pfa' => $this->faker->optional()->company(),
            'rsa' => $this->faker->unique()->numerify('PEN###############'),
            'nhis' => $this->faker->optional()->randomNumber(),
            'address' => $this->faker->optional()->address()
        ];
    }
}
