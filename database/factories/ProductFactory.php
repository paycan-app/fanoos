<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'PRD_' . fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'title' => fake()->words(3, true),
            'category' => fake()->randomElement(['Electronics', 'Clothing', 'Home', 'Sports']),
            'subcategory' => fake()->word(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'brand' => fake()->company(),
            'sku' => fake()->unique()->regexify('SKU-[A-Z0-9]{8}'),
        ];
    }
}
