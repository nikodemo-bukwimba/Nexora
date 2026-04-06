<?php

namespace Modules\Platform\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Platform\Models\OrgInvitation;

class OrgInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrgInvitation $invitation,
        public string $orgName,
        public string $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->orgName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'platform::emails.org-invitation',
        );
    }
}