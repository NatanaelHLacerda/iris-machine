<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'whatsapp' => fake()->optional()->numerify('(##) #####-####'),
            'level' => fake()->randomElement(array_keys(config('iris.levels'))),
            'plan' => fake()->optional()->randomElement(array_column(config('iris.plans'), 'slug')),
            'goal' => fake()->optional()->sentence(),

        ];
    }
}
