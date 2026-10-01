<?php
// app/Mail/EmailVerificationLinkMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $verificationUrl,
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Alamat Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-link',
        );
    }
}
