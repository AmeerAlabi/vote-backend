<?php

namespace App\Mail;

use App\Models\Admin;

class PasswordResetCode extends OneTimeCodeMail
{
    protected function subjectLine(): string
    {
        return 'Reset Your Password';
    }

    protected function intro(): string
    {
        return 'Your password reset code is:';
    }

    protected function expiresIn(): string
    {
        return Admin::PASSWORD_RESET_CODE_TTL_HOURS.' hour';
    }
}
