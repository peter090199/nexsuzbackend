<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A website inquiry. From = the authenticated SMTP mailbox (config mail.contact_from), To = the business recipient
 * saved in Nexsuz Information, Reply-To = the visitor, so "Reply" in the inbox answers the visitor.
 */
class ContactInquiry extends Mailable
{
    public function __construct(
        public string $business,
        public string $recipient,
        public string $visitorName,
        public string $visitorEmail,
        public ?string $visitorPhone,
        public string $subjectLine,
        public string $body,
    ) { }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.contact_from.address'), (string) config('mail.contact_from.name')),
            to: [new Address($this->recipient)],
            replyTo: [new Address($this->visitorEmail, $this->visitorName)],
            subject: '[' . $this->business . ' contact] ' . str_replace(["\r", "\n"], ' ', $this->subjectLine),
        );
    }

    public function content(): Content
    {
        return new Content(html: 'email.contact-inquiry-html', text: 'email.contact-inquiry');
    }
}
