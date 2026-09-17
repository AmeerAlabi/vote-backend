<?php

namespace App\Models;

use Database\Factories\ElectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'allowed_domains', 'active'])]
class Election extends Model
{
    /** @use HasFactory<ElectionFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'allowed_domains' => 'array',
            'active' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function voterSessions(): HasMany
    {
        return $this->hasMany(VoterSession::class);
    }

    /**
     * Normalises domains to lowercase without a leading "@" so that
     * "@School.edu" and "school.edu" are treated the same.
     *
     * @param  array<int, string>  $domains
     * @return array<int, string>
     */
    public static function normaliseDomains(array $domains): array
    {
        return array_values(array_unique(array_map(
            fn (string $domain) => ltrim(strtolower(trim($domain)), '@'),
            $domains,
        )));
    }

    /** Exact domain match — "evilschool.edu" must not satisfy "school.edu". */
    public function allowsEmail(string $email): bool
    {
        $domain = strtolower((string) substr((string) strrchr($email, '@'), 1));

        return $domain !== '' && in_array($domain, $this->allowed_domains, true);
    }
}
