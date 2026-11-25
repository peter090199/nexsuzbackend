<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The ONE Nexsuz business information record, kept in app_settings under "business_profile".
 * Admin, Master Admin and the public Contact section all read and write through this class.
 * The logo lives on the "public" disk under uploads/business/logo/{uuid}/ and is addressed by Storage::url().
 */
class BusinessProfileService
{
    public const KEY = 'business_profile';
    public const LOGO_DIR = 'uploads/business/logo';
    public const OTHER_LINKS_MAX = 5;

    public const TEXT_FIELDS = [
        'business_name', 'registered_name', 'tagline', 'description', 'business_type', 'registration_type', 'registration_number', 'established_on',
        'country', 'region', 'province', 'city', 'barangay', 'street', 'building', 'unit', 'zip_code', 'formatted_address', 'maps_url',
        'phone_primary', 'phone_alt', 'email_official', 'email_support', 'website_url', 'customer_service', 'operating_hours',
        'facebook', 'instagram', 'linkedin', 'youtube', 'twitter', 'tiktok',
        'contact_heading', 'contact_description', 'contact_recipient_email',
    ];
    public const STYLE_MODES = ['transparent', 'white', 'navy', 'color', 'gradient'];
    public const STYLE_ALIGN = ['left', 'center', 'right'];
    /** How the logo container looks wherever the logo is shown. The uploaded image itself is never altered. */
    public const STYLE_DEFAULTS = [
        'bg_mode' => 'white', 'bg_color' => '#ffffff', 'gradient_from' => '#0a66c2', 'gradient_to' => '#002a57', 'gradient_angle' => 135,
        'padding' => 4, 'radius' => 8, 'width' => null, 'height' => null, 'align' => 'left',
    ];

    public const FLAG_FIELDS = ['show_address', 'show_phone', 'show_email', 'show_hours', 'show_socials'];

    public function defaults(): array
    {
        $d = array_fill_keys(self::TEXT_FIELDS, '');
        $d['business_name'] = 'Nexsuz';
        $d['country'] = 'Philippines';
        $d['contact_heading'] = 'Get In Touch';
        $d['contact_description'] = 'Have a question, feedback, or a partnership idea? Reach out — our team typically responds within 24 hours.';

        return $d + array_fill_keys(self::FLAG_FIELDS, true) + ['other_links' => [], 'logo_path' => null, 'logo_style' => self::STYLE_DEFAULTS, 'email_theme' => ['preset' => 'navy']];
    }

    /** Save the record and drop the cached email branding so the next email uses the new name, logo and theme. */
    private function persist(array $record): void
    {
        AppSetting::write(self::KEY, $record);
        app(EmailBrandingService::class)->forget();
    }

    public function tableReady(): bool
    {
        return Schema::hasTable('app_settings');
    }

    public function stored(): ?array
    {
        return $this->tableReady() ? AppSetting::read(self::KEY) : null;
    }

    /** Saved values over the defaults (so a half-filled record never has missing keys). */
    public function record(): array
    {
        $r = array_merge($this->defaults(), $this->stored() ?? []);
        $r['logo_style'] = array_merge(self::STYLE_DEFAULTS, is_array($r['logo_style'] ?? null) ? $r['logo_style'] : []);

        return $r;
    }

    public function logoUrl(?string $path): ?string
    {
        return $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
    }

    public function formattedAddress(array $r): string
    {
        if (trim((string) $r['formatted_address']) !== '') {
            return trim($r['formatted_address']);
        }
        $line1 = trim(implode(' ', array_filter([$r['unit'], $r['building']])));
        $parts = array_filter([$line1, $r['street'], $r['barangay'], $r['city'], $r['province'], $r['region'], trim($r['zip_code'] . ' ' . $r['country'])],
            fn ($p) => trim((string) $p) !== '');

        return implode(', ', $parts);
    }

    /** Everything the admin pages need. */
    public function adminView(): array
    {
        $record = $this->record();
        $view = $record;
        $view['logo_url'] = $this->logoUrl($record['logo_path']);
        unset($view['logo_path']);
        $view['computed_address'] = $this->formattedAddress($record);
        $branding = app(EmailBrandingService::class);
        $view['email_theme'] = $branding->resolve($record['email_theme'] ?? null);
        $view['email_presets'] = $branding->presets();
        $view['saved'] = $this->stored() !== null;

        return $view;
    }

    /** What the public website may see: only what the display switches allow. Never the form recipient. */
    public function publicView(): array
    {
        $r = $this->record();
        $social = array_filter(['facebook' => $r['facebook'], 'instagram' => $r['instagram'], 'linkedin' => $r['linkedin'],
            'youtube' => $r['youtube'], 'twitter' => $r['twitter'], 'tiktok' => $r['tiktok']]);

        return [
            'business_name' => $r['business_name'],
            'tagline' => $r['tagline'],
            'logo_url' => $this->logoUrl($r['logo_path']),
            'logo_style' => $r['logo_style'],
            'contact_heading' => $r['contact_heading'],
            'contact_description' => $r['contact_description'],
            'show_address' => (bool) $r['show_address'], 'show_phone' => (bool) $r['show_phone'], 'show_email' => (bool) $r['show_email'],
            'show_hours' => (bool) $r['show_hours'], 'show_socials' => (bool) $r['show_socials'],
            'address' => $r['show_address'] ? $this->formattedAddress($r) : null,
            'maps_url' => $r['show_address'] ? ($r['maps_url'] ?: null) : null,
            'phone_primary' => $r['show_phone'] ? $r['phone_primary'] : null,
            'phone_alt' => $r['show_phone'] ? $r['phone_alt'] : null,
            'email' => $r['show_email'] ? ($r['email_support'] ?: $r['email_official']) : null,
            'website_url' => $r['website_url'] ?: null,
            'operating_hours' => $r['show_hours'] ? $r['operating_hours'] : null,
            'social' => $r['show_socials'] ? $social : (object) [],
            'other_links' => $r['show_socials'] ? array_values($r['other_links']) : [],
        ];
    }

    public function save(array $input): void
    {
        $next = $this->record();
        foreach (self::TEXT_FIELDS as $f) {
            if (array_key_exists($f, $input)) { $next[$f] = trim((string) ($input[$f] ?? '')); }
        }
        foreach (self::FLAG_FIELDS as $f) {
            if (array_key_exists($f, $input)) { $next[$f] = (bool) $input[$f]; }
        }
        if (isset($input['email_theme']) && is_array($input['email_theme'])) {
            $t = $input['email_theme'];
            $next['email_theme'] = ['preset' => $t['preset'] ?? 'navy', 'colors' => (array) ($t['colors'] ?? []),
                'header_gradient' => (array) ($t['header_gradient'] ?? []), 'footer_gradient' => (array) ($t['footer_gradient'] ?? [])];
        }
        if (isset($input['logo_style']) && is_array($input['logo_style'])) {
            $next['logo_style'] = array_merge($next['logo_style'], array_intersect_key($input['logo_style'], self::STYLE_DEFAULTS));
        }
        if (array_key_exists('other_links', $input)) {
            $next['other_links'] = collect($input['other_links'] ?? [])
                ->map(fn ($l) => ['label' => trim((string) ($l['label'] ?? '')), 'url' => trim((string) ($l['url'] ?? ''))])
                ->filter(fn ($l) => $l['label'] !== '' && $l['url'] !== '')->take(self::OTHER_LINKS_MAX)->values()->all();
        }
        $this->persist($next);
    }

    /** Back to defaults. The logo is kept (it has its own remove button). */
    public function reset(): void
    {
        $logo = $this->record()['logo_path'];
        AppSetting::forget(self::KEY);
        app(EmailBrandingService::class)->forget();
        if ($logo) { $this->persist(['logo_path' => $logo] + $this->defaults()); }
    }

    /** SVG is XML and can carry script. Accept only plain vector drawings. */
    public static function svgIsSafe(string $svg): bool
    {
        if (!preg_match('/<svg[\s>]/i', $svg)) { return false; }
        $banned = ['<script', '<foreignobject', '<iframe', '<embed', '<object', '<!doctype', '<!entity', 'javascript:', 'data:text/html',
            '<animate', '<set ', '<use', '<image', '<link', '<style', 'href="http', "href='http"];
        $lower = strtolower($svg);
        foreach ($banned as $b) {
            if (str_contains($lower, $b)) { return false; }
        }

        return !preg_match('/\son[a-z]+\s*=/i', $svg);
    }

    /** Stores the logo (replacing and removing the previous one) and returns its public URL. */
    public function storeLogo(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $dir = self::LOGO_DIR . '/' . Str::uuid();
        $name = 'logo-' . time() . '.' . $ext;
        $file->storeAs($dir, $name, 'public');

        $record = $this->record();
        $this->removeLogoFile($record['logo_path']);
        $record['logo_path'] = "$dir/$name";
        $this->persist($record);

        return Storage::disk('public')->url($record['logo_path']);
    }

    public function removeLogo(): void
    {
        $record = $this->record();
        $this->removeLogoFile($record['logo_path']);
        $record['logo_path'] = null;
        $this->persist($record);
    }

    /** Only ever deletes inside this module's own folder. */
    private function removeLogoFile(?string $path): void
    {
        if ($path && str_starts_with($path, self::LOGO_DIR . '/') && !str_contains($path, '..')) {
            Storage::disk('public')->deleteDirectory(dirname($path));
        }
    }
}
