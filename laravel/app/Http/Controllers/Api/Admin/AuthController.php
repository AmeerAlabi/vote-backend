<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\SignupRequest;
use App\Http\Requests\Admin\VerifyEmailRequest;
use App\Http\Resources\AdminResource;
use App\Mail\AdminVerificationCode;
use App\Mail\PasswordResetCode;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Admin registration, email verification, login and password reset.
 *
 * Every flow is code-based: a 6-digit code is emailed and exchanged for a
 * Sanctum token. Response shapes intentionally match the original API.
 */
class AuthController extends Controller
{
    /**
     * Register a new admin
     *
     * Creates the account and emails a verification code. The admin cannot
     * log in until the code has been confirmed via `verify-email`.
     */
    public function signup(SignupRequest $request): JsonResponse
    {
        $admin = Admin::create($request->validated());

        Mail::to($admin)->send(new AdminVerificationCode($admin->issueVerificationCode()));

        return response()->json([
            'message' => 'Admin created successfully. Please check your email for the verification code.',
            'email' => $admin->email,
            'requiresVerification' => true,
            'userId' => $admin->id,
        ], 201);
    }

    /**
     * Verify admin email with code
     *
     * If the code has expired a fresh one is emailed automatically.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->string('email'))->first();

        if ($admin === null) {
            return response()->json(['message' => 'Admin not found with this email'], 404);
        }

        if ($admin->isVerified()) {
            return response()->json(['message' => 'Email already verified', 'alreadyVerified' => true]);
        }

        if (! $admin->verificationCodeMatches($request->string('code'))) {
            return response()->json(['message' => 'Invalid verification code'], 400);
        }

        if ($admin->verificationCodeExpired()) {
            Mail::to($admin)->send(new AdminVerificationCode($admin->issueVerificationCode()));

            return response()->json([
                'message' => 'Verification code expired. A new code has been sent to your email.',
                'codeExpired' => true,
            ], 400);
        }

        $admin->markVerified();

        return response()->json([
            'message' => 'Email verified successfully',
            'token' => $admin->issueToken(),
            'admin' => new AdminResource($admin),
        ]);
    }

    /**
     * Login as admin
     *
     * Returns a bearer token valid for 24 hours. An unverified admin receives
     * a 403 and a fresh verification code by email.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->string('email'))->first();

        if ($admin === null || ! Hash::check($request->string('password'), $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 400);
        }

        if (! $admin->isVerified()) {
            Mail::to($admin)->send(new AdminVerificationCode($admin->issueVerificationCode()));

            return response()->json([
                'message' => 'Email not verified. A new verification code has been sent to your email.',
                'requiresVerification' => true,
            ], 403);
        }

        return response()->json([
            'token' => $admin->issueToken(),
            'admin' => new AdminResource($admin),
        ]);
    }

    /**
     * Request password reset
     *
     * Always answers 200 so the endpoint cannot be used to discover accounts.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->string('email'))->first();

        if ($admin !== null) {
            Mail::to($admin)->send(new PasswordResetCode($admin->issuePasswordResetCode()));
        }

        return response()->json([
            'message' => 'If your email is registered, you will receive a password reset code',
            'email' => $request->string('email'),
        ]);
    }

    /**
     * Reset admin password
     *
     * Exchanges the emailed reset code for a new password and logs the admin in.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->string('email'))->first();

        if ($admin === null) {
            return response()->json(['message' => 'Admin not found with this email'], 404);
        }

        if (! $admin->passwordResetCodeMatches($request->string('code'))) {
            return response()->json(['message' => 'Invalid reset code'], 400);
        }

        if ($admin->passwordResetCodeExpired()) {
            return response()->json([
                'message' => 'Reset code has expired. Please request a new code.',
                'codeExpired' => true,
            ], 400);
        }

        $admin->resetPassword($request->string('password'));
        $admin->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully',
            'token' => $admin->issueToken(),
            'admin' => new AdminResource($admin),
        ]);
    }
}
