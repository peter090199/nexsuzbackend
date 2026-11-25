<?php

namespace App\Services;

use App\Mail\ContactInquiry;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends website inquiries through the dedicated "contact" mailer. Its SMTP sign-in comes only from .env
 * (CONTACT_MAIL_*, falling back to MAIL_*). The recipient is the dynamic business email from Nexsuz Information.
 * Nothing here ever returns or logs the SMTP password.
 */
class ContactMailService
{
    private const PROVIDERS = [
        'smtp.gmail.com' => 'Gmail / Google Workspace', 'smtp-relay.gmail.com' => 'Google Workspace relay',
        'smtp.hostinger.com' => 'Hostinger', 'smtp.titan.email' => 'Hostinger (Titan)',
    ];

    public function __construct(private BusinessProfileService $profile) { }

    private function cfg(): array { return (array) config('mail.mailers.contact'); }

    /** The address chosen in Nexsuz Information, valid or not: contact recipient, else support, else official email. */
    public function rawRecipient(): ?string
    {
        $r = $this->profile->record();
        $v = trim((string) ($r['contact_recipient_email'] ?: ($r['email_support'] ?: $r['email_official'])));

        return $v !== '' ? $v : null;
    }

    /** 'missing' (nothing saved), 'invalid' (saved but not a usable address) or null (good). */
    public function recipientProblem(): ?string
    {
        $v = $this->rawRecipient();
        if ($v === null) { return 'missing'; }

        return filter_var($v, FILTER_VALIDATE_EMAIL) && !preg_match('/[
,;<> ]/', $v) ? null : 'invalid';
    }

    /** The business inbox that receives inquiries (dynamic, from Nexsuz Information). Null unless it is valid. */
    public function recipient(): ?string
    {
        return $this->recipientProblem() === null ? $this->rawRecipient() : null;
    }

    private function recipientMessage(): string
    {
        return $this->recipientProblem() === 'invalid'
            ? 'The saved recipient "' . $this->rawRecipient() . '" is not a valid email address. Correct it in Nexsuz Information, Website contact.'
            : 'No recipient email is set. Enter the "Contact form recipient email" in Nexsuz Information, Website contact, and save.';
    }

    /** What is missing from .env to send mail. Names only, never values. */
    public function missing(): array
    {
        $c = $this->cfg();
        $m = [];
        foreach (['host' => 'CONTACT_MAIL_HOST (or MAIL_HOST)', 'username' => 'CONTACT_MAIL_USERNAME (or MAIL_USERNAME)', 'password' => 'CONTACT_MAIL_PASSWORD (or MAIL_PASSWORD)'] as $k => $label) {
            if (trim((string) ($c[$k] ?? '')) === '') { $m[] = $label; }
        }
        if (!filter_var((string) config('mail.contact_from.address'), FILTER_VALIDATE_EMAIL)) { $m[] = 'CONTACT_MAIL_FROM_ADDRESS (or MAIL_FROM_ADDRESS)'; }

        return $m;
    }

    public function configured(): bool { return $this->missing() === []; }

    /** Safe description of the sender setup for the admin page. No password, only whether one is set. */
    public function status(): array
    {
        $c = $this->cfg();
        $host = strtolower((string) ($c['host'] ?? ''));
        $from = (string) config('mail.contact_from.address');
        $warnings = [];
        if ($host === 'smtp.gmail.com' && $c['username'] && $from && strcasecmp($c['username'], $from) !== 0) {
            $warnings[] = 'Gmail only accepts the signed-in mailbox (or a verified alias) as the sender. The sender address differs from the SMTP username.';
        }
        $recipient = $this->recipient();
        $problem = $this->recipientProblem();
        if ($problem) { $warnings[] = $this->recipientMessage(); }

        return [
            'configured' => $this->configured(), 'missing' => $this->missing(),
            'provider' => self::PROVIDERS[$host] ?? ($host ? 'Custom SMTP' : null),
            'host' => $host ?: null, 'port' => $c['port'] ?? null, 'encryption' => $c['encryption'] ?? null,
            'username' => $c['username'] ?: null, 'has_password' => trim((string) ($c['password'] ?? '')) !== '',
            'from_address' => $from ?: null, 'recipient' => $recipient, 'recipient_problem' => $problem, 'warnings' => $warnings,
            'ready' => $this->configured() && (bool) $recipient,
        ];
    }

    /** Open the SMTP connection and sign in, without sending anything. */
    public function checkConnection(): array
    {
        if (!$this->configured()) { return ['ok' => false, 'message' => 'Email sending is not configured. Missing in .env: ' . implode(', ', $this->missing()) . '.']; }
        try {
            $transport = Mail::createSymfonyTransport($this->cfg());
            $transport->start();
            $transport->stop();

            return ['ok' => true, 'message' => 'Connected to ' . $this->cfg()['host'] . ' and signed in.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $this->explain($e)];
        }
    }

    /** Send the inquiry. Returns ok or a visitor-safe failure plus the detailed reason for admins and logs. */
    public function send(string $name, string $email, ?string $phone, string $subject, string $body): array
    {
        $to = $this->recipient();
        if (!$to) { return ['ok' => false, 'status' => 503, 'code' => 'recipient_' . $this->recipientProblem(), 'detail' => $this->recipientMessage()]; }
        if (!$this->configured()) { return ['ok' => false, 'status' => 503, 'code' => 'not_configured', 'detail' => 'Missing in .env: ' . implode(', ', $this->missing()) . '.']; }
        try {
            $business = $this->profile->record()['business_name'] ?: 'Nexsuz';
            Mail::mailer('contact')->send(new ContactInquiry($business, $to, $name, $email, $phone, $subject, $body));

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => 502, 'code' => 'send_failed', 'detail' => $this->explain($e)];
        }
    }

    /**
     * Send an administrator's reply to a visitor from the business mailbox. Returns ok or a clear, secret-free message.
     * @param array<int,array{name:string,mime:string,path:string}> $files on the private disk
     */
    public function deliverReply(string $to, string $name, string $subject, string $html, array $files = []): array
    {
        if (!$this->configured()) { return ['ok' => false, 'message' => 'Email sending is not set up on the server, so the reply was not sent. Missing in .env: ' . implode(', ', $this->missing()) . '.']; }
        if (!filter_var((string) config('mail.contact_from.address'), FILTER_VALIDATE_EMAIL)) { return ['ok' => false, 'message' => 'The business sender email is not configured.']; }
        try {
            Mail::mailer('contact')->send(new \App\Mail\ContactReply($to, $name, $subject, $html, $files));

            return ['ok' => true, 'message' => 'Sent'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $this->explain($e)];
        }
    }

    /** Send the sample email in the saved email theme to the business recipient (admin action). */
    public function sendThemeTest(): array
    {
        $to = $this->recipient();
        if (!$to) { return ['ok' => false, 'message' => $this->recipientMessage()]; }
        if (!$this->configured()) { return ['ok' => false, 'message' => 'Email sending is not set up on the server. Missing in .env: ' . implode(', ', $this->missing()) . '.']; }
        try {
            Mail::mailer('contact')->send(new \App\Mail\ThemeTest($to));

            return ['ok' => true, 'message' => "Theme test email sent to {$to}. Open it in your mail app to check the design."];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $this->explain($e)];
        }
    }

    /** Send a clearly marked test to the configured recipient (admin action). */
    public function sendTest(): array
    {
        $to = $this->recipient();
        if (!$to) { return ['ok' => false, 'message' => $this->recipientMessage()]; }
        $r = $this->send('Nexsuz test', $to, null, 'Test message', 'This is a test of the website contact form. If you can read it, inquiries will reach this inbox.');

        return $r['ok'] ? ['ok' => true, 'message' => "Test email sent to {$to}. Check that inbox (and spam)."] : ['ok' => false, 'message' => $r['detail']];
    }

    /** A meaningful, secret-free explanation of an SMTP failure. */
    public function explain(Throwable $e): string
    {
        $raw = strtolower($e->getMessage());
        $host = (string) ($this->cfg()['host'] ?? 'the mail server');

        return match (true) {
            str_contains($raw, '535') || str_contains($raw, 'authenticat') || str_contains($raw, 'username and password not accepted') || str_contains($raw, 'badcredentials')
                => 'The mail server rejected the sign-in. For Gmail use a Google App Password (2-Step Verification must be on), not the normal password. For a domain mailbox check the username and password.',
            str_contains($raw, 'timed out') || str_contains($raw, 'timeout')
                => "Timed out connecting to {$host}. Check the port and encryption (587 with tls, or 465 with ssl) and that the server allows outgoing SMTP.",
            str_contains($raw, 'could not be established') || str_contains($raw, 'connection refused') || str_contains($raw, 'getaddrinfo') || str_contains($raw, 'name or service not known')
                => "Could not connect to {$host}. Check the host name, port and encryption in .env, and the server's internet access.",
            str_contains($raw, 'ssl') || str_contains($raw, 'tls') || str_contains($raw, 'certificate')
                => 'The secure connection failed. Port 465 needs encryption ssl; port 587 needs tls.',
            str_contains($raw, 'sender') || str_contains($raw, '553') || str_contains($raw, '550') || str_contains($raw, 'not allowed to send')
                => 'The mail server refused the sender or recipient address. The sender must be the signed-in mailbox or a verified alias.',
            default => 'The mail server could not deliver the message (' . class_basename($e) . ').',
        };
    }
}
