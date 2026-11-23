<?php

namespace App\Services\CvImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Saves what a job seeker approved in the import preview, into the tables the CV editor and the profile already use
 * (userprofiles, resources, users, usereducations, usercapabilities, userskills, usertrainings, userseminars,
 * useremploymentrecords, usercertificates). It adds nothing new to the schema.
 *
 * The rules, all enforced here and not on the screen:
 *  - an empty or null value never overwrites what is saved; only fields the person sent AND filled in are written
 *  - nothing is duplicated: an education, job, skill, language, training, seminar or certificate that is already on the
 *    profile (or repeated in the same import) is skipped and counted
 *  - every value is validated; an invalid item is skipped with the reason, the rest of the import still goes through
 *  - it always acts on the signed-in account: the code comes from the caller, never from the request
 *  - the sign-in email of the account is never changed: an imported email is kept as the profile's contact email
 */
class CvImporter
{
    private const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    private const PROFILE_FIELDS = ['fname', 'lname', 'email', 'contact_no', 'profession', 'summary', 'date_birth', 'home_country', 'current_location', 'home_state', 'current_state'];

    /** section => [table, label of one item, columns saved] */
    private const SECTIONS = [
        'education' => ['usereducations', 'education', ['highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year', 'status']],
        'languages' => ['usercapabilities', 'language', ['language']],
        'skills' => ['userskills', 'skill', ['skills']],
        'training' => ['usertrainings', 'training', ['training_title', 'training_provider', 'date_completed']],
        'seminar' => ['userseminars', 'seminar', ['seminar_title', 'seminar_provider', 'date_completed']],
        'employment' => ['useremploymentrecords', 'job', ['company_name', 'position', 'job_description', 'date_completed']],
        'certificate' => ['usercertificates', 'certificate', ['certificate_title', 'certificate_provider', 'date_completed']],
    ];

    // ------------------------------------------------------------------ preview support

    /**
     * Add what the screen needs to the parsed CV: the values already on the profile ("current") and, on every
     * list item, `exists` = it is already saved (so it starts unchecked in the preview).
     */
    public function annotate(object $user, array $parsed): array
    {
        $existing = $this->existingKeys((int) $user->code);
        foreach (self::SECTIONS as $section => $_) {
            foreach ($parsed[$section] ?? [] as $i => $item) {
                $parsed[$section][$i]['exists'] = isset($existing[$section][$this->key($section, $item)]);
            }
        }
        $parsed['current'] = $this->currentProfile($user);

        return $parsed;
    }

    /** the profile as the profile API shows it, for the "currently on your profile" side of the preview */
    public function currentProfile(object $user): array
    {
        $res = DB::table('resources')->where('code', $user->code)->first();
        $prof = DB::table('userprofiles')->where('code', $user->code)->first();
        $u = DB::table('users')->where('id', $user->id)->first();

        return [
            'fname' => $u->fname ?? $res->fname ?? null,
            'lname' => $u->lname ?? $res->lname ?? null,
            'email' => ($prof->email ?? null) ?: ($res->email ?? $u->email ?? null),
            'contact_no' => ($prof->contact_no ?? null) ?: ($res->contact_no ?? null),
            'profession' => $res->profession ?? null,
            'summary' => $prof->summary ?? null,
            'date_birth' => ($prof->date_birth ?? null) ?: ($res->date_birth ?? null),
            'home_country' => ($prof->home_country ?? null) ?: ($res->home_country ?? null),
            'current_location' => ($prof->current_location ?? null) ?: ($res->current_location ?? null),
            'home_state' => $prof->home_state ?? null,
            'current_state' => $prof->current_state ?? null,
        ];
    }

    // ------------------------------------------------------------------ import

    /**
     * @param array{profile?:array, education?:array, languages?:array, skills?:array, training?:array, seminar?:array, employment?:array, certificate?:array} $payload
     * @return array{profile_fields:string[], added:array<string,int>, duplicates:array<string,int>, skipped:array<int,array{section:string,item:string,reason:string}>, total_added:int}
     */
    public function import(object $user, array $payload): array
    {
        $code = (int) $user->code;
        $report = ['profile_fields' => [], 'added' => [], 'duplicates' => [], 'skipped' => [], 'total_added' => 0];

        DB::transaction(function () use ($user, $code, $payload, &$report) {
            $report['profile_fields'] = $this->saveProfile($user, $code, (array) ($payload['profile'] ?? []), $report['skipped']);

            $existing = $this->existingKeys($code);
            foreach (self::SECTIONS as $section => [$table, $label, $columns]) {
                $report['added'][$section] = 0;
                $report['duplicates'][$section] = 0;
                $trans = (int) DB::table($table)->where('code', $code)->max('transNo');
                $seen = $existing[$section] ?? [];

                foreach ((array) ($payload[$section] ?? []) as $item) {
                    if (!is_array($item)) { continue; }
                    $item = $this->clean($section, $item);
                    $name = $this->itemName($section, $item) ?: $label;

                    $v = Validator::make($item, $this->rules($section));
                    if ($v->fails()) {
                        $report['skipped'][] = ['section' => $section, 'item' => $name, 'reason' => $v->errors()->first()];
                        continue;
                    }
                    $key = $this->key($section, $item);
                    if (isset($seen[$key])) { $report['duplicates'][$section]++; continue; }
                    $seen[$key] = true;

                    $row = ['code' => $code, 'transNo' => ++$trans, 'created_at' => now(), 'updated_at' => now()];
                    foreach ($columns as $c) { $row[$c] = $item[$c] ?? null; }
                    DB::table($table)->insert($row);
                    $report['added'][$section]++;
                }
            }
        });

        $report['total_added'] = array_sum($report['added']);

        return $report;
    }

    // ------------------------------------------------------------------ profile

    private function saveProfile(object $user, int $code, array $in, array &$skipped): array
    {
        $values = [];
        foreach (self::PROFILE_FIELDS as $f) {
            $v = isset($in[$f]) && is_scalar($in[$f]) ? trim((string) $in[$f]) : '';
            if ($v === '') { continue; }            // empty never overwrites
            $values[$f] = $v;
        }
        if (!$values) { return []; }

        $v = Validator::make($values, [
            'fname' => 'string|max:255', 'lname' => 'string|max:255', 'email' => 'email|max:255',
            'contact_no' => ['string', 'max:255', 'regex:/^[0-9+()\-\s]*$/'], 'profession' => 'string|max:255', 'summary' => 'string|max:255',
            'date_birth' => 'date_format:Y-m-d|after:1900-01-01|before:today',
            'home_country' => 'string|max:255', 'current_location' => 'string|max:255', 'home_state' => 'string|max:255', 'current_state' => 'string|max:255',
        ], ['contact_no.regex' => 'The contact number may only have digits, spaces, + ( ) and -.']);
        foreach (array_keys($v->errors()->messages()) as $bad) {
            $skipped[] = ['section' => 'profile', 'item' => $bad, 'reason' => $v->errors()->first($bad)];
            unset($values[$bad]);
        }
        if (!$values) { return []; }

        $now = now();

        // the account name (users + resources): a change to either name rebuilds the full name
        $names = array_intersect_key($values, ['fname' => 1, 'lname' => 1]);
        if ($names) {
            $u = DB::table('users')->where('id', $user->id)->first();
            $fname = $names['fname'] ?? $u->fname;
            $lname = $names['lname'] ?? $u->lname;
            $mname = trim((string) ($u->mname ?? ''));
            $full = trim($fname . ' ' . ($mname !== '' ? $mname . ' ' : '') . $lname);
            DB::table('users')->where('id', $user->id)->update(['fname' => $fname, 'lname' => $lname, 'fullname' => $full, 'updated_at' => $now]);
            DB::table('resources')->where('code', $code)->update(['fname' => $fname, 'lname' => $lname, 'fullname' => $full, 'updated_at' => $now]);
        }

        // resources: the columns the profile API reads
        $res = array_intersect_key($values, array_flip(['profession', 'contact_no', 'date_birth', 'home_country', 'current_location']));
        if ($res) { DB::table('resources')->where('code', $code)->update($res + ['updated_at' => $now]); }

        // userprofiles: the profile row (created, without a photo, if this job seeker has none yet)
        $prof = array_intersect_key($values, array_flip(['email', 'contact_no', 'summary', 'date_birth', 'home_country', 'current_location', 'home_state', 'current_state']));
        if ($prof) {
            if (DB::table('userprofiles')->where('code', $code)->exists()) {
                DB::table('userprofiles')->where('code', $code)->update($prof + ['updated_at' => $now]);
            } else {
                DB::table('userprofiles')->insert($prof + [
                    'code' => $code, 'transNo' => (int) DB::table('userprofiles')->max('transNo') + 1,
                    'email' => $prof['email'] ?? $user->email, 'contact_visibility' => 0, 'email_visibility' => 0,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return array_keys($values);
    }

    // ------------------------------------------------------------------ items

    /** only the columns of the section, trimmed, empty -> null */
    private function clean(string $section, array $item): array
    {
        $out = [];
        foreach (self::SECTIONS[$section][2] as $c) {
            $v = $item[$c] ?? null;
            if (is_string($v)) { $v = trim($v); }
            $out[$c] = ($v === '' || $v === null || is_array($v)) ? null : $v;
        }
        foreach (['start_year', 'end_year'] as $y) {
            if (isset($out[$y]) && is_numeric($out[$y])) { $out[$y] = (int) $out[$y]; }
        }

        return $out;
    }

    private function rules(string $section): array
    {
        $date = ['nullable', 'date_format:Y-m-d', 'after:1949-12-31', 'before_or_equal:today'];

        return match ($section) {
            'education' => [
                'highest_education' => 'required|string|max:255', 'school_name' => 'required|string|max:255',
                'start_month' => ['nullable', 'string', 'in:' . implode(',', self::MONTHS)],
                'start_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'end_month' => ['nullable', 'string', 'in:' . implode(',', self::MONTHS)],
                'end_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
                'status' => 'required|in:graduate,undergraduate,ongoing',
            ],
            'languages' => ['language' => 'required|string|max:100'],
            'skills' => ['skills' => 'required|string|max:255'],
            'training' => ['training_title' => 'required|string|max:255', 'training_provider' => 'nullable|string|max:255', 'date_completed' => $date],
            'seminar' => ['seminar_title' => 'required|string|max:255', 'seminar_provider' => 'nullable|string|max:255', 'date_completed' => $date],
            'employment' => ['company_name' => 'required|string|max:255', 'position' => 'required|string|max:255', 'job_description' => 'nullable|string|max:5000', 'date_completed' => $date],
            'certificate' => ['certificate_title' => 'required|string|max:255', 'certificate_provider' => 'nullable|string|max:255', 'date_completed' => $date],
        };
    }

    private function itemName(string $section, array $i): string
    {
        return (string) ($i['school_name'] ?? $i['company_name'] ?? $i['language'] ?? $i['skills'] ?? $i['training_title'] ?? $i['seminar_title'] ?? $i['certificate_title'] ?? '');
    }

    // ------------------------------------------------------------------ duplicates

    private static function norm(mixed $s): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower((string) $s)));
    }

    /** what makes two items "the same": compared without case, punctuation or extra spaces */
    private function key(string $section, array $i): string
    {
        return match ($section) {
            'education' => self::norm($i['highest_education'] ?? '') . '|' . self::norm($i['school_name'] ?? ''),
            'employment' => self::norm($i['position'] ?? '') . '|' . self::norm($i['company_name'] ?? ''),
            'languages' => self::norm($i['language'] ?? ''),
            'skills' => self::norm($i['skills'] ?? ''),
            'training' => self::norm($i['training_title'] ?? '') . '|' . self::norm($i['training_provider'] ?? ''),
            'seminar' => self::norm($i['seminar_title'] ?? '') . '|' . self::norm($i['seminar_provider'] ?? ''),
            'certificate' => self::norm($i['certificate_title'] ?? ''),
        };
    }

    /** @return array<string,array<string,true>> section => set of keys already saved for this account */
    private function existingKeys(int $code): array
    {
        $out = [];
        foreach (self::SECTIONS as $section => [$table, , $columns]) {
            $set = [];
            foreach (DB::table($table)->where('code', $code)->get($columns) as $row) {
                $set[$this->key($section, (array) $row)] = true;
            }
            $out[$section] = $set;
        }

        return $out;
    }
}
