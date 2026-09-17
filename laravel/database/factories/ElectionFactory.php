<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Election>
 */
class ElectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'allowed_domains' => ['school.edu'],
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
