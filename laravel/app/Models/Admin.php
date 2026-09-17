<?php

namespace App\Models;

use App\Support\OneTimeCode;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['email', 'password'])]
#[Hidden(['password', 'verification_code_hash', 'password_reset_code_hash'])]
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public const VERIFICATION_CODE_TTL_HOURS = 24;

    public const PASSWORD_RESET_CODE_TTL_HOURS = 1;

    public const TOKEN_TTL_HOURS = 24;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password_reset_code_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** Generates a new email verification code, stores its hash, and returns the plain code. */
    public function issueVerificationCode(): string
    {
        $code = OneTimeCode::generate();

        $this->forceFill([
            'verification_code_hash' => OneTimeCode::hash($code),
            'verification_code_expires_at' => now()->addHours(self::VERIFICATION_CODE_TTL_HOURS),
        ])->save();

        return $code;
    }

    public function verificationCodeMatches(string $code): bool
    {
        return OneTimeCode::matches($code, $this->verification_code_hash);
    }

    public function verificationCodeExpired(): bool
    {
        return $this->verification_code_expires_at === null || $this->verification_code_expires_at->isPast();
    }

    public function markVerified(): void
    {
        $this->forceFill([
            'email_verified_at' => now(),
            'verification_code_hash' => null,
            'verification_code_expires_at' => null,
        ])->save();
    }

    /** Generates a new password reset code, stores its hash, and returns the plain code. */
    public function issuePasswordResetCode(): string
    {
        $code = OneTimeCode::generate();

        $this->forceFill([
            'password_reset_code_hash' => OneTimeCode::hash($code),
            'password_reset_code_expires_at' => now()->addHours(self::PASSWORD_RESET_CODE_TTL_HOURS),
        ])->save();

        return $code;
    }

    public function passwordResetCodeMatches(string $code): bool
    {
        return OneTimeCode::matches($code, $this->password_reset_code_hash);
    }

    public function passwordResetCodeExpired(): bool
    {
        return $this->password_reset_code_expires_at === null || $this->password_reset_code_expires_at->isPast();
    }

    public function resetPassword(string $password): void
    {
        $this->forceFill([
            'password' => $password,
            'password_reset_code_hash' => null,
            'password_reset_code_expires_at' => null,
        ])->save();
    }

    /** Issues a fresh admin API token; the plain-text value is only available here. */
    public function issueToken(): string
    {
        return $this->createToken('admin', ['admin'], now()->addHours(self::TOKEN_TTL_HOURS))->plainTextToken;
    }
}
