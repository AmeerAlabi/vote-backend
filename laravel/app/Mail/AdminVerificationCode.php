<?php

namespace App\Mail;

use App\Models\Admin;

class AdminVerificationCode extends OneTimeCodeMail
{
    protected function subjectLine(): string
    {
        return 'Verify Your Email';
    }

    protected function intro(): string
    {
        return 'Your verification code is:';
    }

    protected function expiresIn(): string
    {
        return Admin::VERIFICATION_CODE_TTL_HOURS.' hours';
    }
}
