<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A sample email in the saved email theme, sent to the business recipient so the real inbox rendering can be checked. */
class ThemeTest extends Mailable
{
    public function __construct(public string $recipient) { }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.contact_from.address'), (string) config('mail.contact_from.name')),
            to: [new Address($this->recipient)],
            subject: 'Email theme test',
        );
    }

    public function content(): Content
    {
        return new Content(html: 'email.sample', text: 'email.notice-text', with: ['body' => 'This is a test of the Nexsuz email theme. Open this message in an HTML-capable mail app to see the branded design.']);
    }
}
