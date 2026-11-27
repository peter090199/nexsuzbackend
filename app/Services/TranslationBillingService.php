<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Translation plans, pricing and paid Google Cloud Translation usage. Everything is in app_settings:
 *   i18n.google.config  plan, pricing, budget, limits, approval, last connection test
 *   i18n.google.key     the API key, encrypted; only its last 4 characters ever leave the server
 *   i18n.google.usage   {"YYYY-MM": {characters, requests, price, allowance, currency, alerted}}
 *
 * FREE = the translations shipped with Nexsuz: no Google call is ever made.
 * PAID = Google Cloud Translation (v2), used only when an administrator asks to fill missing system texts.
 * Costs here are estimates from the configured price. Google's invoice is the only actual charge.
 */
class TranslationBillingService
{
    private const API = 'https://translation.googleapis.com/language/translate/v2';
    private const CONFIG = 'i18n.google.config';
    private const KEY = 'i18n.google.key';
    private const USAGE = 'i18n.google.usage';

    private const DEFAULTS = [
        'plan' => 'free', 'currency' => 'USD', 'pricing_url' => 'https://cloud.google.com/translate/pricing',
        // no price is built in: the administrator enters the current one from the pricing page
        'price_per_million' => null, 'free_allowance' => 0, 'monthly_budget' => null, 'monthly_char_limit' => null,
        'expected_characters' => 0, 'thresholds' => [50, 80, 100], 'approved_by' => null, 'approved_at' => null, 'test' => null,
    ];

    public function config(): array
    {
        return array_merge(self::DEFAULTS, AppSetting::read(self::CONFIG) ?: []);
    }

    private function write(array $config): void
    {
        AppSetting::write(self::CONFIG, $config);
    }

    public function period(): string
    {
        return now()->format('Y-m');
    }

    private function usage(): array
    {
        return AppSetting::read(self::USAGE) ?: [];
    }

    private function month(array $usage, string $period, array $config): array
    {
        return $usage[$period] ?? ['characters' => 0, 'requests' => 0, 'price' => $config['price_per_million'],
            'allowance' => $config['free_allowance'], 'currency' => $config['currency'], 'alerted' => 0];
    }

    public static function cost(int $characters, ?float $pricePerMillion, int $allowance): float
    {
        return round(max(0, $characters - $allowance) / 1000000 * (float) $pricePerMillion, 6);
    }

    /** how far the month is towards the budget or the character limit, whichever is closer */
    private function percent(int $characters, float $cost, array $config): float
    {
        $budget = $config['monthly_budget'];
        $byBudget = $budget === null ? 0 : ($budget > 0 ? $cost / $budget * 100 : ($cost > 0 ? 100 : 0));
        $byLimit = $config['monthly_char_limit'] ? $characters / $config['monthly_char_limit'] * 100 : 0;

        return round(max($byBudget, $byLimit), 2);
    }

    private function alertLevel(float $percent, array $thresholds): int
    {
        return (int) collect($thresholds)->filter(fn ($t) => $percent >= $t)->max();
    }

    private function summary(string $period, array $row, array $config): array
    {
        $characters = (int) $row['characters'];
        $allowanceUsed = min($characters, (int) $row['allowance']);

        return ['period' => $period, 'characters' => $characters, 'requests' => (int) $row['requests'],
            'allowance' => (int) $row['allowance'], 'allowance_used' => $allowanceUsed,
            'paid_characters' => $characters - $allowanceUsed, 'currency' => $row['currency'],
            'estimated_cost' => self::cost($characters, $row['price'], (int) $row['allowance'])];
    }

    /** what the administrators see. The API key is never part of it. */
    public function view(): array
    {
        $config = $this->config();
        $usage = $this->usage();
        $period = $this->period();
        $current = $this->summary($period, $this->month($usage, $period, $config), $config);
        $percent = $this->percent($current['characters'], $current['estimated_cost'], $config);
        $key = $this->key();
        krsort($usage);

        return [
            'config' => $config,
            'key' => ['saved' => $key !== null, 'masked' => $key === null ? null : '••••••••' . substr($key, -4)],
            'current' => $current + [
                'percent' => $percent,
                'alert_level' => $this->alertLevel($percent, $config['thresholds']),
                'blocked' => $config['plan'] === 'paid' && $percent >= 100,
                'budget_percent' => $config['monthly_budget'] > 0 ? round($current['estimated_cost'] / $config['monthly_budget'] * 100, 2) : null,
            ],
            'expected_cost' => self::cost((int) $config['expected_characters'], $config['price_per_million'], (int) $config['free_allowance']),
            'history' => collect($usage)->map(fn ($row, $p) => $this->summary($p, $row, $config))->values()->take(12)->all(),
        ];
    }

    public function saveSettings(array $values): void
    {
        $config = array_merge($this->config(), $values);
        $thresholds = array_values(array_unique(array_map('intval', $config['thresholds'])));
        sort($thresholds);
        $config['thresholds'] = $thresholds;
        if ($config['plan'] === 'paid' && ($config['price_per_million'] === null || $config['monthly_budget'] === null)) {
            throw new RuntimeException('The paid plan needs a price and a monthly budget. Switch to Free before removing them.');
        }
        $this->write($config);

        // this month's estimate follows a corrected price
        $usage = $this->usage();
        if (isset($usage[$this->period()])) {
            $usage[$this->period()] = array_merge($usage[$this->period()], ['price' => $config['price_per_million'],
                'allowance' => $config['free_allowance'], 'currency' => $config['currency']]);
            AppSetting::write(self::USAGE, $usage);
        }
    }

    // ------------------------------------------------------------------ credential

    private function key(): ?string
    {
        $stored = AppSetting::read(self::KEY);
        if (!$stored) { return null; }
        try {
            return Crypt::decryptString($stored['value']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** a new or removed key always returns to Free: paid use has to be approved again */
    public function saveKey(?string $key): void
    {
        $key === null ? AppSetting::forget(self::KEY) : AppSetting::write(self::KEY, ['value' => Crypt::encryptString($key)]);
        $this->write(array_merge($this->config(), ['plan' => 'free', 'test' => null, 'approved_by' => null, 'approved_at' => null]));
    }

    private function google()
    {
        $key = $this->key();
        if ($key === null) { throw new RuntimeException('Save an API key first.'); }

        // in a header, so the key never appears in a URL or a log of URLs
        return Http::withHeaders(['X-goog-api-key' => $key])->timeout(20);
    }

    /** lists the supported languages: proves the key without translating (no characters are sent) */
    public function testConnection(): array
    {
        $request = $this->google();
        try {
            $response = $request->get(self::API . '/languages', ['target' => 'en']);
            $result = $response->successful()
                ? ['ok' => true, 'message' => 'Connected.']
                : ['ok' => false, 'message' => mb_substr((string) ($response->json('error.message') ?: "Google answered {$response->status()}."), 0, 200)];
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Google could not be reached.'];
        }
        $result['at'] = now()->toIso8601String();
        $config = $this->config();
        $config['test'] = $result;
        if (!$result['ok']) { $config['plan'] = 'free'; }
        $this->write($config);

        return $result;
    }

    // ------------------------------------------------------------------ plan

    public function setPlan(string $plan, bool $confirmed, object $admin): void
    {
        $config = $this->config();
        if ($plan === 'paid') {
            if (!$confirmed) { throw new RuntimeException('Paid translation needs your explicit approval.'); }
            if ($this->key() === null || empty($config['test']['ok'])) { throw new RuntimeException('Save the API key and test the connection first.'); }
            if ($config['price_per_million'] === null || $config['monthly_budget'] === null) { throw new RuntimeException('Set the price per million characters and a monthly budget first.'); }
            $config = array_merge($config, ['approved_by' => $admin->fullname ?? $admin->code, 'approved_at' => now()->toIso8601String()]);
        } else {
            $config = array_merge($config, ['approved_by' => null, 'approved_at' => null]);
        }
        $config['plan'] = $plan;
        $this->write($config);
    }

    // ------------------------------------------------------------------ paid translation

    /** characters one person may have translated with "See translation" in a day, so nobody can drain the budget */
    public const USER_DAILY_CHARACTERS = 20000;

    /** is paid translation switched on and still inside this month's limits? */
    public function available(): bool
    {
        $view = $this->view();

        return $view['config']['plan'] === 'paid' && !$view['current']['blocked'];
    }

    /**
     * Every language Google can translate into, asked from Google itself (no characters are billed).
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function supportedLanguages(): array
    {
        if (!$this->available()) { return []; }
        if ($cached = Cache::get('tr-languages')) { return $cached; }
        $response = $this->google()->get(self::API . '/languages', ['target' => 'en']);
        if (!$response->successful()) { return []; }
        $list = collect($response->json('data.languages') ?: [])
            ->map(fn ($l) => ['code' => (string) $l['language'], 'name' => (string) ($l['name'] ?? $l['language'])])->values()->all();
        if ($list) { Cache::put('tr-languages', $list, now()->addDay()); }

        return $list;
    }

    /**
     * One request to Google, inside the limit and budget. Returns null, sending nothing, if it would pass them.
     * Call it while holding the 'translation-billing' lock.
     *
     * @param string[] $texts
     * @return array{answers: array, characters: int, alert: int}|null
     */
    private function send(array $texts, string $locale, array $config): ?array
    {
        $google = $this->google();
        $characters = array_sum(array_map('mb_strlen', $texts));
        $usage = $this->usage();
        $period = $this->period();
        $row = $this->month($usage, $period, $config);
        $total = $row['characters'] + $characters;
        $cost = self::cost($total, $config['price_per_million'], (int) $config['free_allowance']);
        if (($config['monthly_char_limit'] !== null && $total > $config['monthly_char_limit']) || $cost > $config['monthly_budget']) {
            return null;
        }

        // counted before the call: if the answer is lost, Google may still have billed it
        $usage[$period] = array_merge($row, ['characters' => $total, 'requests' => $row['requests'] + 1, 'price' => $config['price_per_million'],
            'allowance' => $config['free_allowance'], 'currency' => $config['currency']]);
        AppSetting::write(self::USAGE, $usage);

        // no 'source': Google detects the language
        $response = $google->post(self::API, ['q' => array_values($texts), 'target' => self::googleLanguage($locale), 'format' => 'text']);
        if (!$response->successful()) {
            // a rejected request (4xx) is not billed, so it is taken back off the count
            if ($response->clientError()) {
                $usage[$period]['characters'] -= $characters;
                AppSetting::write(self::USAGE, $usage);
            }
            throw new RuntimeException(mb_substr('Google refused the request: ' . ($response->json('error.message') ?: $response->status()), 0, 200));
        }

        $alert = 0;
        $level = $this->alertLevel($this->percent($total, $cost, $config), $config['thresholds']);
        if ($level > ($usage[$period]['alerted'] ?? 0)) {
            $usage[$period]['alerted'] = $level;
            AppSetting::write(self::USAGE, $usage);
            $alert = $level;
        }

        return ['answers' => $response->json('data.translations') ?: [], 'characters' => $characters, 'alert' => $alert];
    }

    /**
     * "See translation": something a person wrote, in the reader's language. Neither the text nor the translation
     * is stored in the database or logged; a copy of the translation is cached, encrypted, so it is paid once.
     *
     * @return array{translated: string, detected: ?string, alert: int}
     */
    public function translateText(string $text, string $locale, int $userId): array
    {
        $config = $this->config();
        if ($config['plan'] !== 'paid') { throw new RuntimeException('Translation is not available.'); }
        $cacheKey = 'tr:' . hash('sha256', self::googleLanguage($locale) . "\0" . $text);
        if ($hit = Cache::get($cacheKey)) {
            return json_decode(Crypt::decryptString($hit), true) + ['alert' => 0];
        }

        return Cache::lock('translation-billing', 60)->block(10, function () use ($text, $locale, $userId, $config, $cacheKey) {
            $dayKey = "tr-user:{$userId}:" . now()->toDateString();
            $today = (int) Cache::get($dayKey, 0) + mb_strlen($text);
            if ($today > self::USER_DAILY_CHARACTERS) { throw new RuntimeException("You have reached today's translation limit."); }

            $sent = $this->send([$text], $locale, $config);
            if ($sent === null) { throw new RuntimeException('Translation is paused for this month.'); }
            Cache::put($dayKey, $today, now()->addDay());

            $result = ['translated' => (string) ($sent['answers'][0]['translatedText'] ?? ''), 'detected' => $sent['answers'][0]['detectedSourceLanguage'] ?? null];
            Cache::put($cacheKey, Crypt::encryptString(json_encode($result)), now()->addDays(7));

            return $result + ['alert' => $sent['alert']];
        });
    }

    public static function googleLanguage(string $locale): string
    {
        // codes Google lists as their own language, as chosen in a chat
        if (preg_match('/^(fr-CA|pt-PT|[a-z]{2,3}-[A-Z][a-z]{3})$/', $locale)) { return $locale; }
        $parts = explode('-', $locale);
        if ($parts[0] === 'zh') { return in_array($parts[1] ?? '', ['TW', 'HK'], true) ? 'zh-TW' : 'zh-CN'; }

        return $parts[0];
    }

    /**
     * Translates texts that have no saved translation and saves the results, so each is paid for once.
     * Stops before any request that would pass the character limit or the budget.
     *
     * @param array<string,string> $texts key => English
     * @return array{translated:int, skipped:int, characters:int, blocked:bool, alert:int}
     */
    public function translate(string $locale, array $texts): array
    {
        return Cache::lock('translation-billing', 120)->block(10, function () use ($locale, $texts) {
            $config = $this->config();
            if ($config['plan'] !== 'paid') { throw new RuntimeException('Paid translation is not enabled.'); }
            $google = $this->google();

            $saved = AppSetting::read("i18n.text.{$locale}") ?: [];
            $todo = array_diff_key($texts, $saved);
            $result = ['translated' => 0, 'skipped' => count($texts) - count($todo), 'characters' => 0, 'blocked' => false, 'alert' => 0];

            foreach (array_chunk($todo, 50, true) as $chunk) {
                $characters = array_sum(array_map('mb_strlen', $chunk));
                $usage = $this->usage();
                $period = $this->period();
                $row = $this->month($usage, $period, $config);
                $total = $row['characters'] + $characters;
                if (($config['monthly_char_limit'] !== null && $total > $config['monthly_char_limit'])
                    || self::cost($total, $config['price_per_million'], (int) $config['free_allowance']) > $config['monthly_budget']) {
                    $result['blocked'] = true;
                    break;
                }

                // counted before the call: if the answer is lost, Google may still have billed it
                $usage[$period] = array_merge($row, ['characters' => $total, 'requests' => $row['requests'] + 1, 'price' => $config['price_per_million'],
                    'allowance' => $config['free_allowance'], 'currency' => $config['currency']]);
                AppSetting::write(self::USAGE, $usage);

                $response = $google->post(self::API, ['q' => array_values($chunk), 'source' => 'en', 'target' => self::googleLanguage($locale), 'format' => 'text']);
                if (!$response->successful()) {
                    // a rejected request (4xx) is not billed, so it is taken back off the count
                    if ($response->clientError()) {
                        $usage[$period]['characters'] -= $characters;
                        AppSetting::write(self::USAGE, $usage);
                    }
                    throw new RuntimeException(mb_substr('Google refused the request: ' . ($response->json('error.message') ?: $response->status()), 0, 200));
                }
                $result['characters'] += $characters;

                $answers = $response->json('data.translations') ?: [];
                $i = 0;
                foreach ($chunk as $key => $english) {
                    $text = trim((string) ($answers[$i++]['translatedText'] ?? ''));
                    // a translation that lost a {0} would show wrong values, so it is not used
                    preg_match_all('/\{\d\}/', $english, $wanted);
                    if ($text === '' || collect($wanted[0])->contains(fn ($p) => !str_contains($text, $p))) {
                        $result['skipped']++;
                        continue;
                    }
                    $saved[$key] = $text;
                    $result['translated']++;
                }
                AppSetting::write("i18n.text.{$locale}", $saved);

                $level = $this->alertLevel($this->percent($total, self::cost($total, $config['price_per_million'], (int) $config['free_allowance']), $config), $config['thresholds']);
                if ($level > ($usage[$period]['alerted'] ?? 0)) {
                    $usage[$period]['alerted'] = $level;
                    AppSetting::write(self::USAGE, $usage);
                    $result['alert'] = $level;
                }
            }

            return $result;
        });
    }
}
