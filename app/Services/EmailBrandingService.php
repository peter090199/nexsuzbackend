<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * One email theme for every Nexsuz email. The choice is stored inside the Business Information record
 * (key "email_theme"), cached, and handed to the shared Blade layout (resources/views/email/layouts/branded.blade.php)
 * by a view composer, so no individual email has to know about colors. Saving business information refreshes the cache.
 */
class EmailBrandingService
{
    public const CACHE_KEY = 'nexsuz.email_branding.v1';
    public const PRESET_IDS = ['navy', 'corporate', 'minimal', 'dark', 'gray', 'custom'];
    public const COLOR_KEYS = ['header_bg', 'header_text', 'body_bg', 'card_bg', 'text', 'footer_bg', 'footer_text', 'primary', 'secondary',
        'button_bg', 'button_text', 'link', 'border', 'divider'];

    private const G_OFF = ['enabled' => false, 'from' => '#002a57', 'to' => '#0a4a94', 'angle' => 135];

    public function presets(): array
    {
        $navy = ['header_bg' => '#002a57', 'header_text' => '#ffffff', 'body_bg' => '#eef2f8', 'card_bg' => '#ffffff', 'text' => '#1b2a4a', 'footer_bg' => '#08203f',
            'footer_text' => '#b9c9e0', 'primary' => '#0a66c2', 'secondary' => '#002a57', 'button_bg' => '#0a66c2', 'button_text' => '#ffffff', 'link' => '#0a66c2',
            'border' => '#dbe3f0', 'divider' => '#e3e9f3'];

        return [
            'navy' => ['label' => 'Professional Navy Blue', 'colors' => $navy, 'header_gradient' => self::G_OFF, 'footer_gradient' => ['from' => '#08203f', 'to' => '#0c3563'] + self::G_OFF],
            'corporate' => ['label' => 'Corporate Blue', 'colors' => ['header_bg' => '#0a66c2', 'header_text' => '#ffffff', 'body_bg' => '#f3f6fb', 'card_bg' => '#ffffff', 'text' => '#1f2937',
                'footer_bg' => '#0f3b78', 'footer_text' => '#cfe0f7', 'primary' => '#0a66c2', 'secondary' => '#1e88e5', 'button_bg' => '#0a66c2', 'button_text' => '#ffffff',
                'link' => '#0a66c2', 'border' => '#d6e2f3', 'divider' => '#e1e9f5'],
                'header_gradient' => ['from' => '#0a66c2', 'to' => '#1e88e5'] + self::G_OFF, 'footer_gradient' => ['from' => '#0f3b78', 'to' => '#0a66c2'] + self::G_OFF],
            'minimal' => ['label' => 'Minimal White', 'colors' => ['header_bg' => '#ffffff', 'header_text' => '#111827', 'body_bg' => '#f9fafb', 'card_bg' => '#ffffff', 'text' => '#374151',
                'footer_bg' => '#f9fafb', 'footer_text' => '#6b7280', 'primary' => '#111827', 'secondary' => '#6b7280', 'button_bg' => '#111827', 'button_text' => '#ffffff',
                'link' => '#111827', 'border' => '#e5e7eb', 'divider' => '#e5e7eb'],
                'header_gradient' => ['from' => '#ffffff', 'to' => '#f3f4f6'] + self::G_OFF, 'footer_gradient' => ['from' => '#f9fafb', 'to' => '#e5e7eb'] + self::G_OFF],
            'dark' => ['label' => 'Dark Technology', 'colors' => ['header_bg' => '#0b1220', 'header_text' => '#e6f1ff', 'body_bg' => '#0f172a', 'card_bg' => '#162033', 'text' => '#e5eaf3',
                'footer_bg' => '#0b1220', 'footer_text' => '#8fa3c0', 'primary' => '#38bdf8', 'secondary' => '#818cf8', 'button_bg' => '#38bdf8', 'button_text' => '#0b1220',
                'link' => '#7dd3fc', 'border' => '#26344d', 'divider' => '#26344d'],
                'header_gradient' => ['enabled' => true, 'from' => '#0b1220', 'to' => '#1e3a5f', 'angle' => 135], 'footer_gradient' => ['from' => '#0b1220', 'to' => '#13233d'] + self::G_OFF],
            'gray' => ['label' => 'Elegant Gray', 'colors' => ['header_bg' => '#374151', 'header_text' => '#ffffff', 'body_bg' => '#f3f4f6', 'card_bg' => '#ffffff', 'text' => '#1f2937',
                'footer_bg' => '#4b5563', 'footer_text' => '#e5e7eb', 'primary' => '#4b5563', 'secondary' => '#9ca3af', 'button_bg' => '#4b5563', 'button_text' => '#ffffff',
                'link' => '#374151', 'border' => '#d1d5db', 'divider' => '#e5e7eb'],
                'header_gradient' => ['from' => '#374151', 'to' => '#4b5563'] + self::G_OFF, 'footer_gradient' => ['from' => '#4b5563', 'to' => '#6b7280'] + self::G_OFF],
        ];
    }

    /** Stored choice -> a complete theme: preset (or custom colors) with gradients and every key present. */
    public function resolve(?array $stored): array
    {
        $presets = $this->presets();
        $id = in_array($stored['preset'] ?? '', self::PRESET_IDS, true) ? $stored['preset'] : 'navy';
        if ($id !== 'custom') {
            $p = $presets[$id];

            return ['preset' => $id, 'colors' => $p['colors'], 'header_gradient' => $p['header_gradient'], 'footer_gradient' => $p['footer_gradient']];
        }
        $base = $presets['navy'];

        return ['preset' => 'custom',
            'colors' => array_merge($base['colors'], array_intersect_key((array) ($stored['colors'] ?? []), $base['colors'])),
            'header_gradient' => array_merge($base['header_gradient'], array_intersect_key((array) ($stored['header_gradient'] ?? []), self::G_OFF)),
            'footer_gradient' => array_merge($base['footer_gradient'], array_intersect_key((array) ($stored['footer_gradient'] ?? []), self::G_OFF))];
    }

    /** The saved theme, cached. */
    public function theme(): array
    {
        return $this->data()['theme'];
    }

    /** What every branded email needs: name, logo, footer details and the theme. Cached until business info changes. */
    public function data(): array
    {
        return Cache::remember(self::CACHE_KEY, 86400, fn () => $this->build(app(BusinessProfileService::class)->record()));
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function build(array $record, ?array $themeOverride = null): array
    {
        $profile = app(BusinessProfileService::class);
        $theme = $this->resolve($themeOverride ?? ($record['email_theme'] ?? null));
        $logo = $profile->logoUrl($record['logo_path'] ?? null);
        $style = (array) ($record['logo_style'] ?? []);
        $mode = $style['bg_mode'] ?? 'white';
        $bg = match ($mode) { 'transparent' => 'transparent', 'navy' => '#002a57', 'color' => $style['bg_color'] ?? '#ffffff', 'gradient' => $style['gradient_from'] ?? '#ffffff', default => '#ffffff' };

        return [
            'name' => $record['business_name'] ?: 'Nexsuz',
            'tagline' => $record['tagline'] ?? '',
            'logo_url' => $logo,
            // email clients ignore most CSS: a flat background, padding and radius are the part that survives everywhere
            'logo_style' => 'display:block;border:0;max-height:44px;max-width:180px;height:auto;background-color:' . $bg . ';padding:' . (int) ($style['padding'] ?? 4) . 'px;border-radius:' . (int) ($style['radius'] ?? 8) . 'px;',
            'website' => $record['website_url'] ?? '',
            'support_email' => $record['email_support'] ?: ($record['email_official'] ?? ''),
            'theme' => $theme,
        ];
    }

    /** CSS for a block background: solid color always (Outlook and others), gradient layered on top where supported. */
    public static function bg(string $solid, array $g): string
    {
        $css = 'background-color:' . $solid . ';';
        if (!empty($g['enabled'])) {
            $css = 'background-color:' . $g['from'] . ';background-image:linear-gradient(' . (int) $g['angle'] . 'deg,' . $g['from'] . ',' . $g['to'] . ');';
        }

        return $css;
    }

    /** The color that must be used as the bgcolor attribute (fallback for clients that ignore CSS backgrounds). */
    public static function solid(string $solid, array $g): string
    {
        return !empty($g['enabled']) ? $g['from'] : $solid;
    }

    /** A complete sample email rendered with the given (possibly unsaved) theme: used by the live preview and the test send. */
    public function renderSample(array $record, ?array $themeOverride = null): string
    {
        return view('email.sample', ['brand' => $this->build($record, $themeOverride)])->render();
    }
}
