<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'election_id' => fn (array $attributes) => Candidate::find($attributes['candidate_id'])->election_id,
            'voter_email' => fake()->unique()->userName().'@school.edu',
        ];
    }
}
