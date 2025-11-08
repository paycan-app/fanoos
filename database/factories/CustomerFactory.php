<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'USR_' . fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'region' => fake()->randomElement(['North', 'South', 'East', 'West']),
            'birthday' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(['Male', 'Female', 'Other']),
            'segment' => fake()->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'channel' => fake()->randomElement(['Web', 'Mobile', 'Store']),
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
