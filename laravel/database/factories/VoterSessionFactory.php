<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\VoterSession;
use App\Support\OneTimeCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoterSession>
 */
class VoterSessionFactory extends Factory
{
    /** Plain-text code stored by the factory so tests can submit it. */
    public const CODE = '123456';

    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'email' => fake()->unique()->userName().'@school.edu',
            'code_hash' => OneTimeCode::hash(self::CODE),
            'verified_at' => null,
            'expires_at' => now()->addMinutes(VoterSession::CODE_TTL_MINUTES),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verified_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }
}
