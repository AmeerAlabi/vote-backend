<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'name' => fake()->name(),
            'bio' => fake()->paragraph(),
            'photo_url' => fake()->imageUrl(300, 300, 'people'),
        ];
    }
}
