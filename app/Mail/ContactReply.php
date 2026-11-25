<?php

namespace App\Mail;

use App\Support\HtmlSanitizer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * An administrator's reply to a website visitor. From = the configured business mailbox (config mail.contact_from, the
 * one authenticated with SMTP), To = the visitor. No Reply-To is added: answers come back to the business inbox.
 */
class ContactReply extends Mailable
{
    /** @param array<int,array{name:string,mime:string,path:string}> $files stored on the private "local" disk */
    public function __construct(
        public string $visitorEmail,
        public string $visitorName,
        public string $subjectLine,
        public string $bodyHtml,
        public array $files = [],
    ) { }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.contact_from.address'), (string) config('mail.contact_from.name')),
            to: [new Address($this->visitorEmail, $this->visitorName)],
            subject: str_replace(["\r", "\n"], ' ', $this->subjectLine),
        );
    }

    public function content(): Content
    {
        return new Content(html: 'email.contact-reply', text: 'email.contact-reply-text', with: ['text' => HtmlSanitizer::toText($this->bodyHtml)]);
    }

    public function attachments(): array
    {
        return array_map(fn ($f) => Attachment::fromStorageDisk('local', $f['path'])->as($f['name'])->withMime($f['mime']), $this->files);
    }
}
