<?php

namespace App\Mail;

use App\Models\Colocation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ColocationInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Colocation $colocation
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'EasyColoc Invitation: '.$this->colocation->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.colocation-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
