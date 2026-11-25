<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\BusinessProfileService as Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Nexsuz business information. One record, shared by the Admin and Master Admin pages and the public Contact section.
 * Management routes sit behind the "businessinfo" middleware; the public routes expose only what the display switches allow.
 */
class BusinessInfoController extends Controller
{
    private const LOGO_MAX_KB = 2048;

    public function __construct(private Profile $profile, private \App\Services\ContactMailService $mail) { }

    private function ready(): ?JsonResponse
    {
        return $this->profile->tableReady() ? null
            : response()->json(['success' => false, 'message' => 'The settings table is missing. Run the app_settings migration first.'], 503);
    }

    private function view(string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $this->profile->adminView()], $status);
    }

    /** GET business-info (admin) */
    public function show(): JsonResponse
    {
        return $this->view();
    }

    /** GET business-info/public (no login): what the website Contact section shows */
    public function publicShow(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->profile->publicView()]);
    }

    /** PUT business-info */
    public function update(Request $request): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }

        $url = ['nullable', 'string', 'max:255', 'url:http,https'];
        $v = Validator::make($request->all(), [
            'business_name' => ['required', 'string', 'max:150'],
            'registered_name' => ['nullable', 'string', 'max:190'],
            'tagline' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'business_type' => ['nullable', 'string', 'max:120'],
            'registration_type' => ['nullable', 'string', 'in:DTI,SEC,CDA,BIR,Other'],
            'registration_number' => ['nullable', 'string', 'max:60'],
            'established_on' => ['nullable', 'date', 'before_or_equal:today'],
            'country' => ['nullable', 'string', 'max:80'], 'region' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'], 'barangay' => ['nullable', 'string', 'max:100'], 'street' => ['nullable', 'string', 'max:150'],
            'building' => ['nullable', 'string', 'max:100'], 'unit' => ['nullable', 'string', 'max:50'], 'zip_code' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9 \-]*$/'],
            'formatted_address' => ['nullable', 'string', 'max:400'],
            'maps_url' => $url,
            'phone_primary' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]*$/'], 'phone_alt' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]*$/'],
            'email_official' => ['nullable', 'email:rfc', 'max:190'], 'email_support' => ['nullable', 'email:rfc', 'max:190'],
            'website_url' => $url,
            'customer_service' => ['nullable', 'string', 'max:190'],
            'operating_hours' => ['nullable', 'string', 'max:400'],
            'facebook' => $url, 'instagram' => $url, 'linkedin' => $url, 'youtube' => $url, 'twitter' => $url, 'tiktok' => $url,
            'other_links' => ['nullable', 'array', 'max:' . Profile::OTHER_LINKS_MAX],
            'other_links.*.label' => ['required_with:other_links.*.url', 'nullable', 'string', 'max:40'],
            'other_links.*.url' => ['required_with:other_links.*.label', 'nullable', 'string', 'max:255', 'url:http,https'],
            'contact_heading' => ['nullable', 'string', 'max:120'],
            'contact_description' => ['nullable', 'string', 'max:500'],
            'contact_recipient_email' => ['nullable', 'email:rfc', 'max:190', 'regex:/^[^\s@,;<>]+@[^\s@,;<>]+\.[A-Za-z]{2,}$/'],   // one real address (Gmail or a domain), never a list
            'email_theme' => ['nullable', 'array'],
            'email_theme.preset' => ['required_with:email_theme', 'in:' . implode(',', \App\Services\EmailBrandingService::PRESET_IDS)],
            'email_theme.colors' => ['nullable', 'array'],
            'email_theme.colors.*' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'email_theme.header_gradient' => ['nullable', 'array'], 'email_theme.footer_gradient' => ['nullable', 'array'],
            'email_theme.header_gradient.enabled' => ['boolean'], 'email_theme.footer_gradient.enabled' => ['boolean'],
            'email_theme.header_gradient.from' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.header_gradient.to' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.header_gradient.angle' => ['nullable', 'integer', 'between:0,360'],
            'email_theme.footer_gradient.from' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.footer_gradient.to' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.footer_gradient.angle' => ['nullable', 'integer', 'between:0,360'],
            'logo_style' => ['nullable', 'array'],
            'logo_style.bg_mode' => ['required_with:logo_style', 'in:' . implode(',', Profile::STYLE_MODES)],
            'logo_style.bg_color' => ['required_with:logo_style', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_style.gradient_from' => ['required_with:logo_style', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_style.gradient_to' => ['required_with:logo_style', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_style.gradient_angle' => ['required_with:logo_style', 'integer', 'between:0,360'],
            'logo_style.padding' => ['required_with:logo_style', 'integer', 'between:0,32'],
            'logo_style.radius' => ['required_with:logo_style', 'integer', 'between:0,60'],
            'logo_style.width' => ['nullable', 'integer', 'between:16,400'],
            'logo_style.height' => ['nullable', 'integer', 'between:16,200'],
            'logo_style.align' => ['required_with:logo_style', 'in:' . implode(',', Profile::STYLE_ALIGN)],
            'show_address' => ['boolean'], 'show_phone' => ['boolean'], 'show_email' => ['boolean'], 'show_hours' => ['boolean'], 'show_socials' => ['boolean'],
        ], [
            'business_name.required' => 'Enter the business name.',
            'url' => 'Enter a full web address that starts with http:// or https://.',
            'established_on.before_or_equal' => 'The establishment date cannot be in the future.',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $this->profile->save($v->validated());
        Log::info('business-info: updated', ['by' => Auth::user()->code ?? null]);

        return $this->view('Business information saved.');
    }

    /** POST business-info/reset : text fields back to defaults (the logo stays) */
    public function reset(): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        $this->profile->reset();
        Log::info('business-info: reset', ['by' => Auth::user()->code ?? null]);

        return $this->view('Business information was reset to the defaults.');
    }

    /** POST business-info/logo */
    public function uploadLogo(Request $request): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }

        $v = Validator::make($request->all(), [
            'logo' => ['required', 'file', 'max:' . self::LOGO_MAX_KB, 'mimes:png,jpg,jpeg,webp,svg'],
        ], [
            'logo.required' => 'Choose a logo file.',
            'logo.max' => 'The logo must be 2 MB or smaller.',
            'logo.mimes' => 'The logo must be a PNG, JPG, JPEG, WEBP or SVG file.',
            'logo.uploaded' => 'The logo could not be uploaded. Check the file size (2 MB maximum).',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $file = $request->file('logo');
        if (strtolower($file->getClientOriginalExtension()) === 'svg' && !Profile::svgIsSafe((string) file_get_contents($file->getRealPath()))) {
            return response()->json(['success' => false, 'message' => 'This SVG contains scripts or external references and cannot be used. Export a plain SVG.', 'errors' => ['logo' => ['unsafe svg']]], 422);
        }

        $this->profile->storeLogo($file);
        Log::info('business-info: logo replaced', ['by' => Auth::user()->code ?? null]);

        return $this->view('Logo updated.');
    }

    /** DELETE business-info/logo */
    public function removeLogo(): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        $this->profile->removeLogo();
        Log::info('business-info: logo removed', ['by' => Auth::user()->code ?? null]);

        return $this->view('Logo removed.');
    }

    /** POST contact-message (no login): the public Contact form. Goes to the configured recipient. */
    public function contact(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'fullName' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]*$/'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $d = $v->validated();

        // 1) keep the message: it appears in Contact Inquiries even if the notification email cannot be sent
        $inquiry = \App\Models\ContactInquiry::create(['name' => $d['fullName'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null, 'subject' => $d['subject'],
            'message' => $d['message'], 'status' => 'new', 'ip' => $request->ip(), 'last_activity_at' => now()]);
        $inquiry->update(['inquiry_no' => sprintf('INQ-%06d', $inquiry->id)]);
        \App\Models\ContactInquiryMessage::create(['inquiry_id' => $inquiry->id, 'direction' => 'in', 'from_email' => $d['email'], 'from_name' => $d['fullName'],
            'to_email' => $this->mail->recipient() ?: (string) config('mail.contact_from.address'), 'subject' => $d['subject'], 'body' => $d['message'], 'is_html' => false, 'status' => 'received']);

        // 2) tell the business inbox (best effort; the visitor's message is already safe)
        $res = $this->mail->send($d['fullName'], $d['email'], $d['phone'] ?? null, $d['subject'], $d['message']);
        if ($res['ok']) {
            $inquiry->update(['notified_at' => now()]);
        } else {
            $inquiry->update(['notify_error' => $res['detail']]);
            Log::warning('contact-message: saved but the notification email was not sent', ['inquiry' => $inquiry->inquiry_no, 'code' => $res['code'], 'reason' => $res['detail']]);
        }

        return response()->json(['success' => true, 'message' => 'Thanks! Your message has been sent.']);
    }

    /** POST business-info/email-theme/preview : renders the sample email with the theme being edited (not saved). Same Blade layout as every real email. */
    public function previewEmailTheme(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email_theme' => ['required', 'array'],
            'email_theme.preset' => ['required', 'in:' . implode(',', \App\Services\EmailBrandingService::PRESET_IDS)],
            'email_theme.colors' => ['nullable', 'array'], 'email_theme.colors.*' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'email_theme.header_gradient' => ['nullable', 'array'], 'email_theme.footer_gradient' => ['nullable', 'array'],
            'email_theme.header_gradient.enabled' => ['boolean'], 'email_theme.footer_gradient.enabled' => ['boolean'],
            'email_theme.header_gradient.from' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.header_gradient.to' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.header_gradient.angle' => ['nullable', 'integer', 'between:0,360'],
            'email_theme.footer_gradient.from' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.footer_gradient.to' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'email_theme.footer_gradient.angle' => ['nullable', 'integer', 'between:0,360'],
            'business_name' => ['nullable', 'string', 'max:150'],
        ]);
        if ($v->fails()) { return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422); }

        $record = $this->profile->record();
        if ($name = trim((string) $request->input('business_name'))) { $record['business_name'] = $name; }
        $html = app(\App\Services\EmailBrandingService::class)->renderSample($record, $v->validated()['email_theme']);

        return response()->json(['success' => true, 'html' => $html]);
    }

    /** POST business-info/email-theme/test : sends the sample email (saved theme) to the business recipient */
    public function testEmailTheme(): JsonResponse
    {
        $r = $this->mail->sendThemeTest();
        Log::info('business-info: email theme test', ['by' => Auth::user()->code ?? null, 'ok' => $r['ok']]);

        return response()->json(['success' => $r['ok'], 'message' => $r['message']], $r['ok'] ? 200 : 422);
    }

    /** GET business-info/contact-mail (admin): sender setup status. Never returns the SMTP password. */
    public function contactMailStatus(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->mail->status()]);
    }

    /** POST business-info/contact-mail/check (admin): connect and sign in to the SMTP server without sending. */
    public function contactMailCheck(): JsonResponse
    {
        $r = $this->mail->checkConnection();

        return response()->json(['success' => $r['ok'], 'message' => $r['message'], 'data' => $this->mail->status()], $r['ok'] ? 200 : 422);
    }

    /** POST business-info/contact-mail/test (admin): send a marked test to the configured recipient. */
    public function contactMailTest(): JsonResponse
    {
        $r = $this->mail->sendTest();
        Log::info('business-info: contact mail test', ['by' => Auth::user()->code ?? null, 'ok' => $r['ok']]);

        return response()->json(['success' => $r['ok'], 'message' => $r['message'], 'data' => $this->mail->status()], $r['ok'] ? 200 : 422);
    }
}
