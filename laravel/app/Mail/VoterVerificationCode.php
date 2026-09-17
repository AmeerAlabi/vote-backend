<?php

namespace App\Mail;

use App\Models\VoterSession;

class VoterVerificationCode extends OneTimeCodeMail
{
    public function __construct(string $code, public readonly string $electionTitle)
    {
        parent::__construct($code);
    }

    protected function subjectLine(): string
    {
        return "Verification Code for {$this->electionTitle}";
    }

    protected function intro(): string
    {
        return "Your verification code for the \"{$this->electionTitle}\" election is:";
    }

    protected function expiresIn(): string
    {
        return (VoterSession::CODE_TTL_MINUTES / 60).' hour';
    }
}
