<?php

use App\Mail\AdminVerificationCode;
use App\Mail\PasswordResetCode;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

/** Pull the plain-text code out of the most recently queued mailable of the given class. */
function queuedCode(string $mailable): string
{
    $code = null;

    Mail::assertQueued($mailable, function (object $mail) use (&$code): bool {
        $code = $mail->code;

        return true;
    });

    return $code;
}

describe('POST /api/admin/signup', function () {
    it('creates an unverified admin and emails a verification code', function () {
        $response = $this->postJson('/api/admin/signup', [
            'email' => 'admin@school.edu',
            'password' => 'Admin123!',
        ]);

        $response->assertCreated()->assertJson([
            'email' => 'admin@school.edu',
            'requiresVerification' => true,
        ]);

        $admin = Admin::firstWhere('email', 'admin@school.edu');
        expect($admin)->not->toBeNull()
            ->and($admin->isVerified())->toBeFalse()
            ->and($response->json('userId'))->toBe($admin->id);

        Mail::assertQueued(AdminVerificationCode::class, fn ($mail) => $mail->hasTo('admin@school.edu'));
    });

    it('rejects a duplicate email with 400', function () {
        Admin::factory()->create(['email' => 'admin@school.edu']);

        $this->postJson('/api/admin/signup', ['email' => 'admin@school.edu', 'password' => 'Admin123!'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Admin already exists']);
    });

    it('rejects short passwords and bad emails with 400', function () {
        $this->postJson('/api/admin/signup', ['email' => 'not-an-email', 'password' => 'short'])
            ->assertStatus(400)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
    });
});

describe('POST /api/admin/verify-email', function () {
    it('verifies the account and returns a token', function () {
        $admin = Admin::factory()->unverified()->create();
        $code = $admin->issueVerificationCode();

        $this->postJson('/api/admin/verify-email', ['email' => $admin->email, 'code' => $code])
            ->assertOk()
            ->assertJsonStructure(['message', 'token', 'admin' => ['id', 'email']]);

        expect($admin->fresh()->isVerified())->toBeTrue();
    });

    it('rejects a wrong code', function () {
        $admin = Admin::factory()->unverified()->create();
        $admin->issueVerificationCode();

        $this->postJson('/api/admin/verify-email', ['email' => $admin->email, 'code' => '000000'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid verification code']);
    });

    it('re-issues a code when the submitted one has expired', function () {
        $admin = Admin::factory()->unverified()->create();
        $code = $admin->issueVerificationCode();
        $this->travel(Admin::VERIFICATION_CODE_TTL_HOURS + 1)->hours();

        $this->postJson('/api/admin/verify-email', ['email' => $admin->email, 'code' => $code])
            ->assertStatus(400)
            ->assertJson(['codeExpired' => true]);

        Mail::assertQueued(AdminVerificationCode::class);
        expect($admin->fresh()->verificationCodeMatches($code))->toBeFalse();
    });

    it('is idempotent for an already verified admin', function () {
        $admin = Admin::factory()->create();

        $this->postJson('/api/admin/verify-email', ['email' => $admin->email, 'code' => '123456'])
            ->assertOk()
            ->assertJson(['alreadyVerified' => true]);
    });

    it('returns 404 for an unknown email', function () {
        $this->postJson('/api/admin/verify-email', ['email' => 'nobody@school.edu', 'code' => '123456'])
            ->assertNotFound();
    });
});

describe('POST /api/admin/login', function () {
    it('returns a token for valid credentials', function () {
        $admin = Admin::factory()->create(['password' => 'Admin123!']);

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'Admin123!'])
            ->assertOk()
            ->assertJsonStructure(['token', 'admin' => ['id', 'email']])
            ->assertJsonPath('admin.id', $admin->id);
    });

    it('rejects a wrong password', function () {
        $admin = Admin::factory()->create(['password' => 'Admin123!']);

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid credentials']);
    });

    it('blocks unverified admins and re-sends a verification code', function () {
        $admin = Admin::factory()->unverified()->create(['password' => 'Admin123!']);

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'Admin123!'])
            ->assertForbidden()
            ->assertJson(['requiresVerification' => true]);

        Mail::assertQueued(AdminVerificationCode::class);
    });
});

describe('password reset', function () {
    it('emails a reset code and accepts it on reset-password', function () {
        $admin = Admin::factory()->create(['password' => 'OldPass123']);

        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $code = queuedCode(PasswordResetCode::class);

        $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'code' => $code,
            'password' => 'NewPass123',
        ])->assertOk()->assertJsonStructure(['token', 'admin']);

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'NewPass123'])->assertOk();
        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'OldPass123'])->assertStatus(400);
    });

    it('does not reveal whether an email is registered', function () {
        $this->postJson('/api/admin/forgot-password', ['email' => 'ghost@school.edu'])->assertOk();

        Mail::assertNothingQueued();
    });

    it('rejects an invalid or expired reset code', function () {
        $admin = Admin::factory()->create();
        $code = $admin->issuePasswordResetCode();

        $this->postJson('/api/admin/reset-password', ['email' => $admin->email, 'code' => '000000', 'password' => 'NewPass123'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid reset code']);

        $this->travel(Admin::PASSWORD_RESET_CODE_TTL_HOURS + 1)->hours();

        $this->postJson('/api/admin/reset-password', ['email' => $admin->email, 'code' => $code, 'password' => 'NewPass123'])
            ->assertStatus(400)
            ->assertJson(['codeExpired' => true]);
    });

    it('revokes existing tokens when the password is reset', function () {
        $admin = Admin::factory()->create();
        $admin->issueToken();
        $code = $admin->issuePasswordResetCode();

        $this->postJson('/api/admin/reset-password', ['email' => $admin->email, 'code' => $code, 'password' => 'NewPass123'])
            ->assertOk();

        expect($admin->tokens()->count())->toBe(1);
    });
});

it('throttles repeated auth attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/admin/login', ['email' => "user{$i}@school.edu", 'password' => 'x']);
    }

    $this->postJson('/api/admin/login', ['email' => 'user11@school.edu', 'password' => 'x'])
        ->assertStatus(429);
});
