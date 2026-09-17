<?php

namespace App\Models;

use App\Support\OneTimeCode;
use Database\Factories\VoterSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A voter's email-verification session for one election. Doubles as the
 * authenticatable behind the "voter" guard so a vote token is bound to it.
 */
#[Fillable(['election_id', 'email'])]
#[Hidden(['code_hash'])]
class VoterSession extends Authenticatable
{
    /** @use HasFactory<VoterSessionFactory> */
    use HasApiTokens, HasFactory, HasUuids, Prunable;

    public const CODE_TTL_MINUTES = 60;

    public const VOTE_TOKEN_TTL_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now());
    }

    /** Issues a fresh code (resetting verification), stores its hash, and returns the plain code. */
    public function issueCode(): string
    {
        $code = OneTimeCode::generate();

        $this->forceFill([
            'code_hash' => OneTimeCode::hash($code),
            'verified_at' => null,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ])->save();

        $this->tokens()->delete();

        return $code;
    }

    public function codeMatches(string $code): bool
    {
        return OneTimeCode::matches($code, $this->code_hash);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** Marks the session verified and returns a short-lived token scoped to this election. */
    public function markVerifiedAndIssueVoteToken(): string
    {
        $this->forceFill(['verified_at' => now()])->save();
        $this->tokens()->delete();

        return $this->createToken(
            'vote',
            [self::voteAbility($this->election_id)],
            now()->addMinutes(self::VOTE_TOKEN_TTL_MINUTES),
        )->plainTextToken;
    }

    public static function voteAbility(string $electionId): string
    {
        return "vote:{$electionId}";
    }
}
