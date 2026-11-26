<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Countries, languages, translations and each user's language. Countries and languages are separate lists.
 *
 * Reference data (ISO 3166 / ISO 639) is read only: database/data/*.json. Only what people change is stored, in
 * app_settings:
 *   i18n.countries            {inactive: [codes]}
 *   i18n.languages            {active: [codes], default: code, custom: {code: {name, native, rtl, countries}}}
 *   i18n.text.{locale}        {key: translated text}      (overrides the files shipped with the app)
 *   i18n.user.{users.id}      {language: code}
 *
 * Writes by an administrator are behind the 'masteradmin' middleware (see routes).
 */
class LocalizationController extends Controller
{
    private const BUILT_IN_ACTIVE = ['en-US', 'fil-PH', 'zh-CN'];
    private const FALLBACK = 'en-US';
    private const LOCALE = '/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/';

    private static function reference(string $file): array
    {
        static $cache = [];

        return $cache[$file] ??= json_decode(file_get_contents(database_path("data/{$file}.json")), true);
    }

    private function languageState(): array
    {
        $s = AppSetting::read('i18n.languages') ?: [];

        return ['active' => $s['active'] ?? self::BUILT_IN_ACTIVE, 'default' => $s['default'] ?? self::FALLBACK, 'custom' => $s['custom'] ?? []];
    }

    /** every language: reference ones plus the administrator's additions and edits, with active / default flags */
    private function languages(): array
    {
        $state = $this->languageState();
        $all = collect(self::reference('languages'))->keyBy('code')->all();
        foreach ($state['custom'] as $code => $data) {
            $all[$code] = array_merge($all[$code] ?? ['code' => $code], $data);
        }

        return collect($all)->map(fn ($l) => $l + ['active' => in_array($l['code'], $state['active'], true), 'is_default' => $l['code'] === $state['default']])
            ->sortBy('name')->values()->all();
    }

    private function activeLanguage(string $code): ?array
    {
        return collect($this->languages())->first(fn ($l) => $l['code'] === $code && $l['active']);
    }

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    // =================================================================== public

    /** GET i18n/config : what the language picker needs */
    public function config()
    {
        return response()->json(['success' => true, 'default' => $this->languageState()['default'],
            'languages' => collect($this->languages())->where('active', true)
                ->map(fn ($l) => ['code' => $l['code'], 'name' => $l['name'], 'native' => $l['native'] ?? '', 'rtl' => (bool) ($l['rtl'] ?? false)])->values()]);
    }

    /** GET i18n/countries : the active countries, for the country picker */
    public function countries()
    {
        $inactive = AppSetting::read('i18n.countries')['inactive'] ?? [];

        return response()->json(['success' => true, 'data' => collect(self::reference('countries'))
            ->reject(fn ($c) => in_array($c['code'], $inactive, true))
            ->map(fn ($c) => ['code' => $c['code'], 'code3' => $c['code3'], 'name' => $c['name']])->values()]);
    }

    /** GET i18n/translations/{locale} : the administrator's texts for one language */
    public function translations(string $locale)
    {
        if (!preg_match(self::LOCALE, $locale)) { return $this->fail('Unknown language.', 404); }

        // base language first, the country variant on top
        $text = [];
        foreach (array_unique([explode('-', $locale)[0], $locale]) as $code) {
            $text = array_merge($text, AppSetting::read("i18n.text.{$code}") ?: []);
        }

        return response()->json(['success' => true, 'data' => (object) $text]);
    }

    // =================================================================== own preference

    public function preference()
    {
        $saved = AppSetting::read('i18n.user.' . Auth::id()) ?: [];
        $code = $saved['language'] ?? null;

        // a language the administrator has switched off is no longer used
        return response()->json(['success' => true, 'language' => $code && $this->activeLanguage($code) ? $code : null,
            'country' => $saved['country'] ?? null]);
    }

    public function savePreference(Request $request)
    {
        // language and country are separate choices: either one may be saved on its own
        $v = $request->validate([
            'language' => 'required_without:country|string|max:20',
            'country' => 'required_without:language|string|in:' . implode(',', array_column(self::reference('countries'), 'code')),
        ]);
        if (isset($v['language']) && !$this->activeLanguage($v['language'])) { return $this->fail('That language is not available.'); }
        $key = 'i18n.user.' . Auth::id();
        $saved = array_merge(AppSetting::read($key) ?: [], $v);
        AppSetting::write($key, $saved);

        return response()->json(['success' => true] + $saved);
    }

    // =================================================================== administration: people's preferences

    /** GET i18n/users?search=&page= : accounts with the language each one saved (null = automatic) */
    public function users(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $page = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('fullname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('fullname')->orderBy('id')
            ->paginate(20, ['id', 'fullname', 'email', 'role_code']);
        $saved = AppSetting::whereIn('setting_key', $page->getCollection()->map(fn ($u) => "i18n.user.{$u->id}"))->pluck('value', 'setting_key');

        return response()->json(['success' => true, 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(),
            'data' => $page->getCollection()->map(fn ($u) => ['id' => $u->id, 'name' => $u->fullname, 'email' => $u->email, 'role_code' => $u->role_code,
                'language' => json_decode($saved["i18n.user.{$u->id}"] ?? 'null', true)['language'] ?? null])->values()]);
    }

    /** PUT i18n/users/{id} {language} : null returns that person to automatic (browser, then system default) */
    public function setUserLanguage(Request $request, int $id)
    {
        $code = $request->validate(['language' => 'present|nullable|string|max:20'])['language'];
        if (!User::whereKey($id)->exists()) { return $this->fail('User not found.', 404); }
        if ($code !== null && !$this->activeLanguage($code)) {
            return $this->fail('That language is not available.');
        }
        $saved = AppSetting::read("i18n.user.{$id}") ?: [];      // their country stays
        unset($saved['language']);
        if ($code !== null) { $saved['language'] = $code; }
        $saved ? AppSetting::write("i18n.user.{$id}", $saved) : AppSetting::forget("i18n.user.{$id}");

        return response()->json(['success' => true, 'message' => 'User language updated.']);
    }

    // =================================================================== administration

    /** GET i18n/admin */
    public function admin()
    {
        $inactive = AppSetting::read('i18n.countries')['inactive'] ?? [];

        return response()->json(['success' => true,
            'countries' => array_map(fn ($c) => $c + ['active' => !in_array($c['code'], $inactive, true)], self::reference('countries')),
            'languages' => $this->languages()]);
    }

    /** PUT i18n/countries/{code} {active} */
    public function setCountry(Request $request, string $code)
    {
        $active = (bool) $request->validate(['active' => 'required|boolean'])['active'];
        if (!in_array($code, array_column(self::reference('countries'), 'code'), true)) { return $this->fail('Unknown country.', 404); }

        $inactive = array_values(array_diff(AppSetting::read('i18n.countries')['inactive'] ?? [], [$code]));
        if (!$active) { $inactive[] = $code; }
        AppSetting::write('i18n.countries', ['inactive' => $inactive]);

        return response()->json(['success' => true, 'message' => 'Country updated.']);
    }

    /** POST i18n/languages : add one, or edit one (reference or added) */
    public function saveLanguage(Request $request)
    {
        $v = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:' . self::LOCALE],
            'name' => 'required|string|max:100', 'native' => 'nullable|string|max:100', 'rtl' => 'required|boolean',
            'countries' => 'array|max:250', 'countries.*' => 'string|in:' . implode(',', array_column(self::reference('countries'), 'code')),
        ]);
        $state = $this->languageState();
        $state['custom'][$v['code']] = ['code' => $v['code'], 'name' => $v['name'], 'native' => $v['native'] ?? '',
            'rtl' => (bool) $v['rtl'], 'countries' => array_values($v['countries'] ?? [])];
        AppSetting::write('i18n.languages', $state);

        return response()->json(['success' => true, 'message' => 'Language saved.']);
    }

    /** PUT i18n/languages/{code} {active?, is_default?} */
    public function setLanguage(Request $request, string $code)
    {
        $v = $request->validate(['active' => 'boolean', 'is_default' => 'boolean']);
        if (!collect($this->languages())->firstWhere('code', $code)) { return $this->fail('Unknown language.', 404); }
        $state = $this->languageState();

        if (array_key_exists('active', $v)) {
            // English is the fallback of every translation, and the default must stay usable
            if (!$v['active'] && $code === self::FALLBACK) { return $this->fail('English (United States) is the fallback and stays active.'); }
            if (!$v['active'] && $code === $state['default']) { return $this->fail('Choose another default language first.'); }
            $state['active'] = array_values(array_diff($state['active'], [$code]));
            if ($v['active']) { $state['active'][] = $code; }
        }
        if (!empty($v['is_default'])) {
            if (!in_array($code, $state['active'], true)) { return $this->fail('Activate the language before making it the default.'); }
            $state['default'] = $code;
        }
        AppSetting::write('i18n.languages', $state);

        return response()->json(['success' => true, 'message' => 'Language updated.']);
    }

    /** PUT i18n/translations/{locale} {values: {key: text|null}} : null or '' removes the override */
    public function saveTranslations(Request $request, string $locale)
    {
        if (!collect($this->languages())->firstWhere('code', $locale)) { return $this->fail('Unknown language.', 404); }
        $values = $request->validate(['values' => 'required|array|max:2000', 'values.*' => 'nullable|string|max:2000'])['values'];

        $text = AppSetting::read("i18n.text.{$locale}") ?: [];
        foreach ($values as $key => $value) {
            if (!preg_match('/^[A-Za-z0-9_]{1,120}$/', (string) $key)) { return $this->fail('Invalid translation key.'); }
            if ($value === null || trim($value) === '') { unset($text[$key]); } else { $text[$key] = $value; }
        }
        AppSetting::write("i18n.text.{$locale}", $text);

        return response()->json(['success' => true, 'message' => 'Translations saved.']);
    }
}
