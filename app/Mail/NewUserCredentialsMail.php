<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
        public bool $passwordWasGenerated,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tes accès Pronostop14'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.users.new-user-credentials'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
