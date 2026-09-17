<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Base for every "here is your 6-digit code" email. Subclasses only decide
 * the subject, the intro sentence and how long the code lives.
 */
abstract class OneTimeCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code) {}

    abstract protected function subjectLine(): string;

    abstract protected function intro(): string;

    abstract protected function expiresIn(): string;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.code',
            with: [
                'subject' => $this->subjectLine(),
                'intro' => $this->intro(),
                'expiresIn' => $this->expiresIn(),
            ],
        );
    }
}
