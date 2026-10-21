<?php

namespace Database\Factories;

use App\Models\Lga;
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

        // Pick the LGA first and take its state, so the pair is always
        // coherent — a random state and a random LGA rarely belong together.
        $lga = Lga::inRandomOrder()->first();

        return [
            // Cast: Str::ulid() hands back a Ulid object, and everything that
            // joins on this column compares it as a string.
            'ulid' => (string) Str::ulid(),
            'file_number' => $this->faker->unique()->numerify('FILE-#####'),
            'computer_number' => $this->faker->unique()->numerify('COMP-#####'),
            'surname' => $this->faker->lastName($gender[rand(0, 1)]),
            'firstname' => $this->faker->firstName($gender[rand(0, 1)]),
            'othernames' => $this->faker->name($gender[rand(0, 1)]),
            'date_of_birth' => $this->faker->date(),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'state_of_origin' => $lga?->state_id,
            'lga_of_origin' => $lga?->id,
            'pfa' => $this->faker->optional()->company(),
            'rsa' => $this->faker->unique()->numerify('PEN###############'),
            'nhis' => $this->faker->optional()->randomNumber(),
            'address' => $this->faker->optional()->address()
        ];
    }
}
