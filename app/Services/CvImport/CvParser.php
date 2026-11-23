<?php

namespace App\Services\CvImport;

/**
 * Turns the plain text of a CV / resume into the structure of a job seeker's profile.
 *
 * Rules this class lives by:
 *  - it only reports what the text says. A field it cannot find or is not sure about stays empty (null / [])
 *    and is listed in `warnings`; it never guesses a name, a date or a school.
 *  - it is a best-effort reader of English CVs: the person always reviews and edits the result before anything is saved.
 *  - it never touches the database: saving, de-duplication and validation are CvImporter's job.
 *
 * parse() returns:
 *   profile    fname lname email contact_no profession summary date_birth home_country current_location home_state current_state
 *   education  [highest_education school_name start_month start_year end_month end_year status]
 *   languages  [language]            skills [skills]
 *   training   [training_title training_provider date_completed]     seminar [seminar_title seminar_provider date_completed]
 *   employment [company_name position job_description date_completed]
 *   certificate[certificate_title certificate_provider date_completed]
 *   warnings   string[]
 */
class CvParser
{
    private const MONTH_RE = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
    private const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    /** heading text (lower case, letters only) => section key. Anything under "ignore" is skipped. */
    private const HEADINGS = [
        'summary' => ['summary', 'professional summary', 'career summary', 'profile', 'professional profile', 'personal profile', 'career objective', 'objective', 'objectives', 'about me', 'about', 'personal statement', 'executive summary'],
        'experience' => ['experience', 'work experience', 'professional experience', 'employment', 'employment history', 'employment record', 'work history', 'career history', 'relevant experience', 'job experience', 'working experience'],
        'education' => ['education', 'educational background', 'educational attainment', 'education and training', 'academic background', 'academic qualifications', 'academic history', 'education history'],
        'skills' => ['skills', 'technical skills', 'key skills', 'core skills', 'core competencies', 'competencies', 'skills and abilities', 'skills and expertise', 'skills and competencies', 'areas of expertise', 'expertise', 'technologies', 'tech stack', 'professional skills', 'soft skills', 'computer skills', 'skill set', 'skillset', 'technical proficiencies'],
        'languages' => ['languages', 'language', 'language proficiency', 'language skills', 'languages spoken'],
        'certificate' => ['certifications', 'certification', 'certificates', 'certificate', 'licenses', 'license', 'licenses and certifications', 'certifications and licenses', 'professional certifications', 'licensure', 'licenses and certificates', 'certifications and trainings'],
        'training' => ['training', 'trainings', 'training programs', 'trainings attended', 'professional training', 'training and development', 'courses', 'trainings and seminars', 'training and seminars', 'seminars and trainings', 'seminars and training', 'trainings and seminars attended', 'training and seminars attended'],
        'seminar' => ['seminars', 'seminar', 'seminars attended', 'workshops', 'seminars and workshops', 'workshops and seminars', 'conferences', 'seminars workshops'],
        'personal' => ['personal information', 'personal details', 'personal data', 'personal', 'contact', 'contact information', 'contact details', 'contact me', 'bio data', 'biodata', 'bio-data'],
        'ignore' => ['references', 'character references', 'reference', 'declaration', 'interests', 'hobbies', 'hobbies and interests', 'interests and hobbies', 'awards', 'achievements', 'awards and achievements', 'honors', 'projects', 'volunteer', 'volunteer experience', 'volunteering', 'publications', 'affiliations', 'memberships', 'activities', 'extracurricular activities', 'additional information', 'other information'],
    ];

    private const JOB_WORDS = 'developer|engineer|manager|analyst|assistant|officer|director|consultant|specialist|administrator|designer|accountant|teacher|instructor|professor|nurse|supervisor|coordinator|clerk|technician|representative|lead|head|intern|trainee|associate|programmer|architect|executive|secretary|staff|agent|auditor|planner|writer|editor|researcher|scientist|president|owner|founder|cashier|driver|operator|foreman|mechanic|electrician|therapist|pharmacist|physician|doctor|lawyer|attorney|paralegal|recruiter|marketer|strategist|freelancer|tester|qa|devops|sysadmin|support|helper|cook|chef|waiter|bartender|barista|stylist|sales|encoder|virtual';

    /** these words end or name an organisation: "Acme Inc.", "Globex Corporation" */
    private const COMPANY_STRONG = 'inc|incorporated|corp|corporation|company|co|ltd|llc|limited|group|bank|university|college|school|hospital|foundation|holdings|enterprises|enterprise|trading|partners|industries|institute|clinic|agency';

    /** these also appear in job titles ("Software Engineer", "Network Administrator"): only a hint */
    private const COMPANY_WEAK = 'solutions|technologies|technology|systems|services|studio|studios|labs|laboratory|office|department|international|center|centre|restaurant|hotel|network|software|media|logistics|manufacturing|construction|realty|insurance|telecom|outsourcing|bpo';

    private const COUNTRIES = ['afghanistan', 'albania', 'algeria', 'andorra', 'angola', 'argentina', 'armenia', 'australia', 'austria', 'azerbaijan', 'bahamas', 'bahrain', 'bangladesh', 'barbados', 'belarus', 'belgium', 'belize', 'benin', 'bhutan', 'bolivia', 'bosnia and herzegovina', 'botswana', 'brazil', 'brunei', 'bulgaria', 'burkina faso', 'burundi', 'cambodia', 'cameroon', 'canada', 'chad', 'chile', 'china', 'colombia', 'congo', 'costa rica', 'croatia', 'cuba', 'cyprus', 'czech republic', 'czechia', 'denmark', 'dominican republic', 'ecuador', 'egypt', 'el salvador', 'estonia', 'ethiopia', 'fiji', 'finland', 'france', 'gabon', 'gambia', 'georgia', 'germany', 'ghana', 'greece', 'guatemala', 'guinea', 'guyana', 'haiti', 'honduras', 'hong kong', 'hungary', 'iceland', 'india', 'indonesia', 'iran', 'iraq', 'ireland', 'israel', 'italy', 'ivory coast', 'jamaica', 'japan', 'jordan', 'kazakhstan', 'kenya', 'kuwait', 'kyrgyzstan', 'laos', 'latvia', 'lebanon', 'lesotho', 'liberia', 'libya', 'lithuania', 'luxembourg', 'macau', 'madagascar', 'malawi', 'malaysia', 'maldives', 'mali', 'malta', 'mauritius', 'mexico', 'moldova', 'monaco', 'mongolia', 'montenegro', 'morocco', 'mozambique', 'myanmar', 'namibia', 'nepal', 'netherlands', 'new zealand', 'nicaragua', 'niger', 'nigeria', 'north korea', 'north macedonia', 'norway', 'oman', 'pakistan', 'palestine', 'panama', 'papua new guinea', 'paraguay', 'peru', 'philippines', 'poland', 'portugal', 'puerto rico', 'qatar', 'romania', 'russia', 'rwanda', 'saudi arabia', 'senegal', 'serbia', 'sierra leone', 'singapore', 'slovakia', 'slovenia', 'somalia', 'south africa', 'south korea', 'korea', 'spain', 'sri lanka', 'sudan', 'suriname', 'sweden', 'switzerland', 'syria', 'taiwan', 'tajikistan', 'tanzania', 'thailand', 'togo', 'trinidad and tobago', 'tunisia', 'turkey', 'turkiye', 'turkmenistan', 'uganda', 'ukraine', 'united arab emirates', 'uae', 'united kingdom', 'uk', 'great britain', 'england', 'scotland', 'wales', 'united states', 'united states of america', 'usa', 'us', 'uruguay', 'uzbekistan', 'venezuela', 'vietnam', 'yemen', 'zambia', 'zimbabwe'];

    private array $warnings = [];

    public function parse(string $text): array
    {
        $this->warnings = [];
        $lines = $this->lines($text);
        [$header, $sections] = $this->sections($lines);

        $profile = $this->profile($header, $sections, $lines);

        $result = [
            'profile' => $profile,
            'education' => $this->education($sections['education'] ?? []),
            'languages' => $this->languages($sections['languages'] ?? []),
            'skills' => $this->skills($sections['skills'] ?? []),
            'training' => [],
            'seminar' => [],
            'employment' => $this->employment($sections['experience'] ?? []),
            'certificate' => $this->certificates($sections['certificate'] ?? []),
        ];
        [$result['training'], $result['seminar']] = $this->trainingAndSeminars($sections['training'] ?? [], $sections['seminar'] ?? []);

        foreach (['education' => 'education', 'employment' => 'work experience', 'skills' => 'skills'] as $k => $label) {
            if (!$result[$k]) { $this->warnings[] = "No {$label} section was found."; }
        }
        $result['warnings'] = array_values(array_unique($this->warnings));

        return $result;
    }

    // ================================================================== text -> lines -> sections

    /** @return string[] trimmed lines (blank lines kept as '') */
    private function lines(string $text): array
    {
        $text = str_replace(["\r\n", "\r", "\f", "\xC2\xA0", "\t"], ["\n", "\n", "\n", ' ', ' '], $text);
        $out = [];
        foreach (explode("\n", $text) as $l) {
            $l = trim(preg_replace('/\s{2,}/u', '  ', $l));
            // a bullet character at the start of a line becomes a plain dash
            $l = preg_replace('/^[\x{2022}\x{25CF}\x{25AA}\x{25E6}\x{00B7}\x{2023}\x{2043}\x{25AB}\x{2219}\*]\s*/u', '- ', $l);
            $out[] = $l;
        }

        return $out;
    }

    private function headingKey(string $line): ?array
    {
        $l = trim($line);
        if ($l === '' || mb_strlen($l) > 70) { return null; }

        // "Skills: PHP, Laravel" => heading + the rest of the line as its first content line
        if (preg_match('/^([A-Za-z][A-Za-z &\/\-]{2,40}?)\s*[:\x{2013}\-]\s*(\S.*)$/u', $l, $m)) {
            $k = $this->aliasKey($m[1]);
            if ($k && in_array($k, ['summary', 'skills', 'languages', 'certificate', 'training', 'seminar'], true)) {
                return [$k, $m[2]];
            }
        }
        $k = $this->aliasKey($l);

        return $k ? [$k, null] : null;
    }

    private function aliasKey(string $text): ?string
    {
        $t = strtolower(trim(preg_replace('/[^A-Za-z&\- ]+/', ' ', $text)));
        $t = trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', $t)));
        foreach (self::HEADINGS as $key => $aliases) {
            if (in_array($t, $aliases, true)) { return $key; }
        }

        return null;
    }

    /** @return array{0:string[], 1:array<string,string[]>} the lines before the first heading, and the lines under each heading */
    private function sections(array $lines): array
    {
        $header = [];
        $sections = [];
        $current = null;
        foreach ($lines as $line) {
            $h = $this->headingKey($line);
            if ($h) {
                $current = $h[0];
                $sections[$current] ??= [];
                if ($h[1] !== null) { $sections[$current][] = $h[1]; }
                continue;
            }
            if ($current === null) { $header[] = $line; }
            else { $sections[$current][] = $line; }
        }
        unset($sections['ignore']);

        return [$header, $sections];
    }

    // ================================================================== personal information

    private function profile(array $header, array $sections, array $allLines): array
    {
        $personal = $sections['personal'] ?? [];
        $top = array_slice($header, 0, 14);
        $scan = array_merge($top, $personal);                // where contact details live
        $all = implode("\n", $allLines);

        $p = [
            'fname' => null, 'lname' => null, 'email' => null, 'contact_no' => null, 'profession' => null, 'summary' => null,
            'date_birth' => null, 'home_country' => null, 'current_location' => null, 'home_state' => null, 'current_state' => null,
        ];

        // ---- email and phone
        if (preg_match('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}/', implode("\n", $scan) ?: $all, $m)
            || preg_match('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}/', $all, $m)) {
            $p['email'] = strtolower($m[0]);
        }
        $p['contact_no'] = $this->phone($scan);
        if (!$p['contact_no']) { $p['contact_no'] = $this->phone($allLines, true); }

        // ---- name
        [$p['fname'], $p['lname'], $nameLine] = $this->name($top, $personal);
        if (!$p['fname']) { $this->warnings[] = 'The name could not be found.'; }

        // ---- profession
        $p['profession'] = $this->profession($top, $personal, $nameLine);

        // ---- date of birth
        $p['date_birth'] = $this->dateOfBirth($all);

        // ---- addresses
        $found = $this->labelledAddresses($allLines);
        $current = $found['current'] ?? $found['generic'] ?? $this->headerAddress($scan);
        $home = $found['home'] ?? null;
        if ($current) {
            $p['current_location'] = $current['city'];
            $p['current_state'] = $current['state'];
        }
        if ($home) { $p['home_state'] = $home['state'] ?? $home['city']; }
        $p['home_country'] = $home['country'] ?? $current['country'] ?? null;

        // ---- summary
        if (!empty($sections['summary'])) {
            $s = trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($sections['summary'], fn ($l) => $l !== ''))));
            $s = trim(preg_replace('/(^|\s)-\s+/', '$1', $s));
            if ($s !== '') {
                if (mb_strlen($s) > 255) {
                    $cut = mb_substr($s, 0, 255);
                    $dot = max((int) mb_strrpos($cut, '. '), (int) mb_strrpos($cut, ' '));
                    $s = rtrim(mb_substr($cut, 0, $dot > 120 ? $dot : 255), " ,;-") . (str_ends_with(rtrim(mb_substr($cut, 0, $dot > 120 ? $dot : 255)), '.') ? '' : '');
                    $this->warnings[] = 'The summary was shortened to 255 characters.';
                }
                $p['summary'] = $s;
            }
        }

        return $p;
    }

    private function phone(array $lines, bool $labelledOnly = false): ?string
    {
        foreach ($lines as $line) {
            if ($line === '') { continue; }
            $line = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}/', ' ', $line);
            $labelled = (bool) preg_match('/(?:phone|mobile|cell|tel|telephone|contact|whatsapp|viber|call)\b/i', $line);
            if ($labelledOnly && !$labelled) { continue; }
            // not a year range, not a date
            $probe = preg_replace('/\b(?:19|20)\d{2}\s*[-\x{2013}]\s*(?:(?:19|20)\d{2}|present)\b/iu', ' ', $line);
            if (preg_match_all('/(?<![\d\/])(\+?\d[\d\s().\-]{6,}\d)(?![\d\/])/', $probe, $mm)) {
                foreach ($mm[1] as $cand) {
                    $digits = preg_replace('/\D/', '', $cand);
                    if (strlen($digits) < 7 || strlen($digits) > 15) { continue; }
                    if (preg_match('/^(?:19|20)\d{2}$/', $digits)) { continue; }
                    return trim(preg_replace('/\s{2,}/', ' ', $cand));
                }
            }
        }

        return null;
    }

    /** @return array{0:?string,1:?string,2:?string} first name, last name and the line they came from */
    private function name(array $top, array $personal): array
    {
        // "Name: Juan Dela Cruz" wins over guessing
        foreach (array_merge($personal, $top) as $line) {
            if (preg_match('/^(?:full\s+)?name\s*[:\-]\s*(.+)$/i', $line, $m)) {
                $r = $this->splitName($m[1]);
                if ($r) { return [$r[0], $r[1], $line]; }
            }
        }
        foreach ($top as $line) {
            if ($line === '' || $this->headingKey($line)) { continue; }
            if (preg_match('/@|https?:|www\.|linkedin|github|\d{3}/i', $line)) { continue; }
            if (preg_match('/\b(resume|r.sum.|curriculum|vitae|biodata|bio-data|bio data|cv|page|application)\b/iu', $line)) { continue; }
            if (preg_match('/\b(' . self::JOB_WORDS . ')\b/i', $line)) { continue; }
            $r = $this->splitName($line);
            if ($r) { return [$r[0], $r[1], $line]; }
        }

        return [null, null, null];
    }

    /** @return ?array{0:string,1:string} */
    private function splitName(string $raw): ?array
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $raw));
        if ($raw === '' || mb_strlen($raw) > 60) { return null; }

        $parts = array_map('trim', explode(',', $raw));
        // "MBA", "CPA, RN", "Jr.": capital-letter abbreviations (case matters: "Maria Clara" is a name) or a known suffix
        $credential = fn (string $s) => (bool) preg_match('/^[A-Z]{2,5}(?:\s+[A-Z]{2,5})*\.?$/', $s)
            || (bool) preg_match('/^(?:jr|sr|ii|iii|iv|ph\.?d|cpa|rn|mba|msc|bsc|esq)\.?$/i', $s);
        $lastFirst = false;
        if (count($parts) >= 2) {
            if ($credential($parts[1])) { $raw = $parts[0]; }                       // "John Smith, MBA"
            elseif (count(preg_split('/\s+/', $parts[1])) <= 3) { $lastFirst = true; } // "Smith, John"
            else { return null; }
        }

        $tokens = $lastFirst ? preg_split('/\s+/', $parts[1] . ' ' . $parts[0]) : preg_split('/\s+/', $raw);
        $tokens = array_values(array_filter($tokens, fn ($t) => !preg_match('/^(?:mr|mrs|ms|miss|dr|engr|atty|prof)\.?$/i', $t)));
        // trailing suffixes (Jr., III) are not part of the surname
        while (count($tokens) > 2 && preg_match('/^(?:jr|sr|ii|iii|iv)\.?$/i', end($tokens))) { array_pop($tokens); }
        if (count($tokens) < 2 || count($tokens) > 5) { return null; }
        foreach ($tokens as $t) {
            if (!preg_match('/^[\p{L}][\p{L}.\'\x{2019}\-]*$/u', $t)) { return null; }
        }

        $title = fn (string $s) => $s === mb_strtoupper($s) || $s === mb_strtolower($s) ? mb_convert_case($s, MB_CASE_TITLE) : $s;

        return [$title($tokens[0]), $title(end($tokens))];
    }

    private function profession(array $top, array $personal, ?string $nameLine): ?string
    {
        foreach (array_merge($personal, $top) as $line) {
            if (preg_match('/^(?:position|job\s+title|profession|desired\s+position|applying\s+for|occupation|designation)\s*[:\-]\s*(.{2,70})$/i', $line, $m)) {
                return $this->titleCase(trim($m[1]));
            }
        }
        if ($nameLine === null) { return null; }
        $i = array_search($nameLine, $top, true);
        if ($i === false) { return null; }
        for ($j = $i + 1; $j <= $i + 3 && $j < count($top); $j++) {
            $line = $top[$j];
            if ($line === '') { continue; }
            if ($this->headingKey($line) || preg_match('/@|https?:|www\.|\d/', $line)) { continue; }
            if (mb_strlen($line) > 70) { return null; }
            $words = preg_split('/\s+/', $line);
            if (preg_match('/\b(' . self::JOB_WORDS . ')\b/i', $line) || (count($words) <= 6 && !str_contains($line, ',') && !preg_match('/\b(street|st\.|avenue|road|city|province)\b/i', $line))) {
                return $this->titleCase($line);
            }

            return null;
        }

        return null;
    }

    private function titleCase(string $s): string
    {
        return $s === mb_strtoupper($s) ? mb_convert_case($s, MB_CASE_TITLE) : $s;
    }

    private function dateOfBirth(string $all): ?string
    {
        if (!preg_match('/(?:date\s+of\s+birth|birth\s*date|birthday|d\.?o\.?b\.?|born(?:\s+on)?)\s*[:\-]?\s*([^\n]{4,40})/i', $all, $m)) {
            return null;
        }
        $d = $this->parseDate($m[1]);
        if (!$d) { return null; }
        if (!empty($d['ambiguous'])) {
            $this->warnings[] = 'The date of birth could not be read without guessing (day and month are ambiguous). Please enter it.';
            return null;
        }
        if (!$d['m'] || !$d['d'] || !checkdate($d['m'], $d['d'], $d['y'])) { return null; }
        $age = (int) date('Y') - $d['y'];
        if ($age < 14 || $age > 100) { return null; }

        return sprintf('%04d-%02d-%02d', $d['y'], $d['m'], $d['d']);
    }

    /**
     * "Present Address: ...", "Permanent Address: ...", "Address: ...", "Location: ..." lines.
     * @return array{current?:array,home?:array,generic?:array}
     */
    private function labelledAddresses(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            if ($line === '' || !preg_match('/^([A-Za-z ]{0,25}(?:address|location|residence|city)[A-Za-z ]{0,15}|based in)\s*[:\-]\s*(.{4,150})$/i', $line, $m)) { continue; }
            $kind = preg_match('/permanent|home|provincial/i', $m[1]) ? 'home' : (preg_match('/present|current/i', $m[1]) ? 'current' : 'generic');
            if (isset($out[$kind])) { continue; }
            $a = $this->parseAddress($m[2]);
            if ($a) { $out[$kind] = $a; }
        }

        return $out;
    }

    /** the contact line at the top: "email | phone | City, State, Country" */
    private function headerAddress(array $lines): ?array
    {
        foreach ($lines as $line) {
            foreach (preg_split('/\s*[|\x{2022}\x{00B7}]\s*/u', $line) as $seg) {
                if (preg_match('/@|https?:|www\.|\d{4,}/', $seg) || mb_strlen($seg) < 5 || !str_contains($seg, ',')) { continue; }
                $a = $this->parseAddress($seg);
                if ($a && ($a['country'] || ($a['city'] && $a['state']))) { return $a; }
            }
        }

        return null;
    }

    /** @return ?array{city:?string,state:?string,country:?string} */
    private function parseAddress(string $s): ?array
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', trim($s, " .;")))));
        // drop postal codes and phone-like pieces
        $parts = array_values(array_filter($parts, fn ($x) => !preg_match('/^\d{3,6}(?:-\d{3,4})?$/', $x)));
        if (!$parts) { return null; }

        $country = null;
        $last = strtolower(trim(preg_replace('/\d+/', '', end($parts))));
        if (in_array($last, self::COUNTRIES, true)) {
            $country = $this->titleCase(trim(preg_replace('/\d+/', '', array_pop($parts))));
            if (in_array(strtolower($country), ['usa', 'uk', 'uae', 'us'], true)) { $country = strtoupper($country); }
        }
        // a street line ("123 Mabini St.") is not a city
        $parts = array_values(array_filter($parts, fn ($x) => !preg_match('/\d/', $x)
            && !preg_match('/\b(?:st|street|ave|avenue|rd|road|blk|block|lot|brgy|barangay|purok|subdivision|village|unit|floor|bldg|building)\b\.?/i', $x)));

        $city = $state = null;
        if (count($parts) >= 2) { $city = $parts[count($parts) - 2]; $state = $parts[count($parts) - 1]; }
        elseif (count($parts) === 1) { $city = $parts[0]; }
        if (!$city && !$state && !$country) { return null; }
        if ($city && mb_strlen($city) > 80) { return null; }

        return ['city' => $city, 'state' => $state, 'country' => $country];
    }

    // ================================================================== dates

    /** @return ?array{y:int,m:?int,d:?int,ambiguous?:bool} */
    private function parseDate(string $s): ?array
    {
        $s = trim($s);
        $mon = self::MONTH_RE;
        if (preg_match('/\b((?:19|20)\d{2})-(\d{1,2})-(\d{1,2})\b/', $s, $m)) {
            return ['y' => (int) $m[1], 'm' => (int) $m[2], 'd' => (int) $m[3]];
        }
        if (preg_match('/\b(' . $mon . ')\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+((?:19|20)\d{2})\b/i', $s, $m)) {
            return ['y' => (int) $m[3], 'm' => $this->monthNo($m[1]), 'd' => (int) $m[2]];
        }
        if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s+(' . $mon . ')\.?,?\s+((?:19|20)\d{2})\b/i', $s, $m)) {
            return ['y' => (int) $m[3], 'm' => $this->monthNo($m[2]), 'd' => (int) $m[1]];
        }
        if (preg_match('/\b(\d{1,2})[\/.\-](\d{1,2})[\/.\-]((?:19|20)\d{2})\b/', $s, $m)) {
            [$a, $b] = [(int) $m[1], (int) $m[2]];
            if ($a > 12 && $b <= 12) { return ['y' => (int) $m[3], 'm' => $b, 'd' => $a]; }
            if ($b > 12 && $a <= 12) { return ['y' => (int) $m[3], 'm' => $a, 'd' => $b]; }
            if ($a === $b) { return ['y' => (int) $m[3], 'm' => $a, 'd' => $b]; }

            return ['y' => (int) $m[3], 'm' => null, 'd' => null, 'ambiguous' => true];
        }
        if (preg_match('/\b(' . $mon . ')\.?,?\s*((?:19|20)\d{2})\b/i', $s, $m)) {
            return ['y' => (int) $m[2], 'm' => $this->monthNo($m[1]), 'd' => null];
        }
        if (preg_match('/\b(\d{1,2})[\/\-]((?:19|20)\d{2})\b/', $s, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12) {
            return ['y' => (int) $m[2], 'm' => (int) $m[1], 'd' => null];
        }
        if (preg_match('/\b((?:19|20)\d{2})\b/', $s, $m)) {
            return ['y' => (int) $m[1], 'm' => null, 'd' => null];
        }

        return null;
    }

    private function monthNo(string $name): int
    {
        return (int) (array_search(strtolower(substr($name, 0, 3)), array_map(fn ($x) => strtolower(substr($x, 0, 3)), self::MONTHS), true) + 1);
    }

    private function monthName(?int $n): ?string
    {
        return $n && $n >= 1 && $n <= 12 ? self::MONTHS[$n - 1] : null;
    }

    /** a date range inside a line: ['start'=>date,'end'=>date|null,'present'=>bool,'rest'=>line without the range] */
    private function range(string $line): ?array
    {
        $mon = self::MONTH_RE;
        $tok = '(?:(?:' . $mon . ')\.?,?\s*)?(?:19|20)\d{2}|\d{1,2}[\/\-](?:19|20)\d{2}';
        $re = '/\(?\b(' . $tok . ')\s*(?:-|\x{2013}|\x{2014}|to|until|till)\s*(' . $tok . '|present|current|now|ongoing|to date|today)\b\)?/iu';
        if (!preg_match($re, $line, $m)) { return null; }
        $start = $this->parseDate($m[1]);
        $present = (bool) preg_match('/^(?:present|current|now|ongoing|to date|today)$/i', trim($m[2]));
        $end = $present ? null : $this->parseDate($m[2]);
        if (!$start || (!$present && !$end)) { return null; }

        return ['start' => $start, 'end' => $end, 'present' => $present, 'rest' => trim(str_replace($m[0], ' ', $line), " \t-|,;()")];
    }

    private function dbDate(?array $d): ?string
    {
        if (!$d || empty($d['y']) || !empty($d['ambiguous'])) { return null; }
        $today = (int) date('Y');
        if ($d['y'] < 1950 || $d['y'] > $today) { return null; }
        $m = $d['m'] ?: 1;     // only a year is known: January (the person can correct it in the preview)
        $day = $d['d'] ?: 1;

        return checkdate($m, $day, $d['y']) ? sprintf('%04d-%02d-%02d', $d['y'], $m, $day) : null;
    }

    // ================================================================== blocks

    /** @return string[][] groups of non-blank lines; blank lines separate them */
    private function blocks(array $lines): array
    {
        $blocks = [];
        $cur = [];
        foreach ($lines as $l) {
            if ($l === '') {
                if ($cur) { $blocks[] = $cur; $cur = []; }
                continue;
            }
            $cur[] = $l;
        }
        if ($cur) { $blocks[] = $cur; }

        return $blocks;
    }

    private function isBullet(string $l): bool { return str_starts_with($l, '- '); }

    // ================================================================== education

    private function education(array $lines): array
    {
        $degreeRe = '/\b(?:bachelor|bachelors|b\.?s\.?c?\.?|b\.?a\.?|a\.?b\.?|master|masters|m\.?s\.?c?\.?|m\.?a\.?|mba|ph\.?d|doctor(?:ate)?|associate|diploma|certificate|high\s+school|senior\s+high|junior\s+high|secondary|primary|elementary|vocational|technical|undergraduate|bsit|bscs|bsba|bsa|bsn|bsed|beed|abm|stem|humss|tesda)\b/i';
        $schoolRe = '/\b(?:university|college|institute|school|academy|polytechnic|universidad|seminary|campus)\b/i';

        $entries = [];
        foreach ($this->entryBlocks($lines, fn ($l) => preg_match($degreeRe, $l) || preg_match($schoolRe, $l)) as $block) {
            $degree = $school = null;
            $range = null;
            $flags = implode(' ', $block);
            foreach ($block as $line) {
                if (!$range && ($r = $this->range($line))) { $range = $r; }
            }
            foreach ($block as $line) {
                $text = $this->range($line)['rest'] ?? $line;
                $text = trim(preg_replace('/^-\s*/', '', $text), " \t,;|");
                if ($text === '') { continue; }
                // a level line ("Senior High School - STEM", "Elementary") is the degree, whatever follows the dash
                if (!$degree && preg_match('/^(?:senior\s+high\s+school|junior\s+high\s+school|high\s+school|secondary(?:\s+education)?|elementary|primary(?:\s+education)?)\b/i', $text)
                    && !preg_match('/\b(?:university|college|institute|academy|polytechnic)\b/i', $text)) {
                    $degree = $text;
                    continue;
                }
                // "BS Computer Science, Ateneo University" / "BS CS - Ateneo" / "Ateneo University | BS CS"
                $segments = preg_split('/\s*(?:\||\x{2013}|\x{2014}| - |,| at )\s*/u', $text);
                foreach ($segments as $seg) {
                    $seg = trim($seg);
                    if ($seg === '' || mb_strlen($seg) > 120) { continue; }
                    if (!$school && preg_match($schoolRe, $seg) && !preg_match('/^(?:high\s+school|senior\s+high|junior\s+high)$/i', $seg)) { $school = $seg; continue; }
                    if (!$degree && preg_match($degreeRe, $seg)) { $degree = $seg; continue; }
                    if (!$school && preg_match('/high\s+school/i', $seg) && preg_match($schoolRe, $seg)) { $school = $seg; }
                }
            }
            if (!$degree && !$school) { continue; }
            if ($school && !$degree && preg_match('/\bhigh\s+school\b/i', $school)) { $degree = 'High School'; }

            $startY = $range['start']['y'] ?? null;
            $endY = $range ? ($range['end']['y'] ?? null) : null;
            $startM = $this->monthName($range['start']['m'] ?? null);
            $endM = $this->monthName($range['end']['m'] ?? null);
            if (!$range && preg_match('/\b((?:19|20)\d{2})\b/', $flags, $y)) { $endY = (int) $y[1]; }

            $status = '';
            if (($range && $range['present']) || preg_match('/\b(?:ongoing|current|expected|in progress|present)\b/i', $flags)) { $status = 'ongoing'; }
            elseif (preg_match('/\b(?:undergraduate|incomplete|did not (?:finish|graduate)|not graduate)\b/i', $flags)) { $status = 'undergraduate'; }
            elseif ($endY || preg_match('/\bgraduat/i', $flags)) { $status = 'graduate'; }

            $thisYear = (int) date('Y');
            if ($startY && ($startY > $thisYear || $startY < 1900)) { $startY = null; $startM = null; }
            if ($endY && ($endY > $thisYear + 10 || $endY < 1900)) { $endY = null; $endM = null; }
            if ($status === 'ongoing' && $endY && $endY <= $thisYear && ($range['present'] ?? false)) { $endY = null; $endM = null; }

            $entries[] = [
                'highest_education' => $degree ? $this->titleCase($degree) : null,
                'school_name' => $school ? $this->titleCase($school) : null,
                'start_month' => $startY ? $startM : null, 'start_year' => $startY,
                'end_month' => $endY ? $endM : null, 'end_year' => $endY,
                'status' => $status ?: null,
            ];
        }

        return $this->cap($entries, 15);
    }

    /**
     * Group the lines of a section into entries: blank lines separate them; with no blank lines, a new entry starts when
     * the next line looks like the start of another one ($starts) and the current entry already has its own start.
     * @return string[][]
     */
    private function entryBlocks(array $lines, callable $starts): array
    {
        $blocks = $this->blocks($lines);
        if (count($blocks) > 1) { return $blocks; }
        $flat = $blocks[0] ?? [];
        $out = [];
        $cur = [];
        $curHasStart = false;
        foreach ($flat as $l) {
            $isStart = !$this->isBullet($l) && (bool) $starts($l);
            $hasRange = (bool) $this->range($l);
            if ($cur && $curHasStart && ($isStart && ($hasRange || $this->blockHasRange($cur))) ) {
                $out[] = $cur;
                $cur = [];
                $curHasStart = false;
            }
            $cur[] = $l;
            $curHasStart = $curHasStart || $isStart;
        }
        if ($cur) { $out[] = $cur; }

        return $out;
    }

    private function blockHasRange(array $block): bool
    {
        foreach ($block as $l) { if ($this->range($l)) { return true; } }

        return false;
    }

    // ================================================================== work experience

    private function employment(array $lines): array
    {
        $jobRe = '/\b(?:' . self::JOB_WORDS . ')\b/i';
        $entries = [];
        $blocks = $this->entryBlocks($lines, fn ($l) => preg_match($jobRe, $l) || $this->range($l));

        foreach ($blocks as $block) {
            $range = null;
            $head = [];
            $desc = [];
            foreach ($block as $line) {
                if ($this->isBullet($line)) { $desc[] = trim(substr($line, 2)); continue; }
                $r = $this->range($line);
                if ($r && !$range) {
                    $range = $r;
                    if ($r['rest'] !== '') { $head[] = $r['rest']; }
                    continue;
                }
                // a heading line is short and is not a sentence; "Acme Inc." ends in a period but is not a sentence
                $sentence = preg_match('/[.!?]$/', $line) && !preg_match('/\b(?:inc|corp|co|ltd|llc|jr|sr|st)\.$/i', $line);
                if (count($head) < 3 && mb_strlen($line) <= 110 && !$sentence) { $head[] = $line; }
                else { $desc[] = $line; }
            }
            if (!$head) { continue; }

            [$position, $company] = $this->positionAndCompany($head, $jobRe);
            if (!$position && !$company) { continue; }

            $entries[] = [
                'company_name' => $company,
                'position' => $position,
                'job_description' => $desc ? mb_substr(implode("\n", $desc), 0, 2000) : null,
                'date_completed' => $range && !$range['present'] ? $this->dbDate($range['end']) : null,
            ];
        }

        return $this->cap($entries, 20);
    }

    /** @return array{0:?string,1:?string} position and company from the heading lines of one job */
    private function positionAndCompany(array $head, string $jobRe): array
    {
        $pieces = [];
        foreach ($head as $h) {
            foreach (preg_split('/\s*(?:\||\x{2013}|\x{2014}| - | at | @ |,)\s*/u', $h) as $p) {
                $p = trim($p, " \t-|,;()");
                if ($p !== '' && mb_strlen($p) <= 90 && !preg_match('/^(?:philippines|manila|quezon city|makati|cebu|davao|remote|full[- ]time|part[- ]time|contract|freelance)$/i', $p)) { $pieces[] = $p; }
            }
        }
        $strongRe = '/\b(?:' . self::COMPANY_STRONG . ')\b\.?/i';
        $weakRe = '/\b(?:' . self::COMPANY_WEAK . ')\b/i';
        $position = $company = null;
        foreach ($pieces as $p) {
            $isJob = (bool) preg_match($jobRe, $p);
            $strong = (bool) preg_match($strongRe, $p);
            if (!$position && $isJob && !$strong) { $position = $p; continue; }
            if (!$company && ($strong || (!$isJob && preg_match($weakRe, $p)))) { $company = $p; }
        }
        // no keyword hit: the first two pieces in the order written ("Position" then "Company"), nothing more
        if (!$position && !$company && count($pieces) >= 2) { [$position, $company] = [$pieces[0], $pieces[1]]; }
        elseif (!$company) {
            foreach ($pieces as $p) { if ($p !== $position) { $company = $p; break; } }
        } elseif (!$position) {
            foreach ($pieces as $p) { if ($p !== $company) { $position = $p; break; } }
        }

        return [$position ? $this->titleCase($position) : null, $company ? $this->titleCase($company) : null];
    }

    // ================================================================== skills / languages

    private function skills(array $lines): array
    {
        $items = [];
        foreach ($lines as $l) {
            if ($l === '') { continue; }
            $l = preg_replace('/^-\s*/', '', $l);
            if (preg_match('/^[A-Za-z][A-Za-z &\/]{1,30}:\s*(.+)$/', $l, $m)) { $l = $m[1]; }   // "Languages: PHP, JS"
            foreach (preg_split('/\s*[,;|\x{2022}\x{00B7}]\s*/u', $l) as $s) {
                $s = trim($s, " .-\t");
                if ($s === '' || mb_strlen($s) < 2 || mb_strlen($s) > 60) { continue; }
                if (count(preg_split('/\s+/', $s)) > 6) { continue; }              // a sentence, not a skill
                $items[mb_strtolower($s)] ??= $s;
            }
        }

        return $this->cap(array_map(fn ($s) => ['skills' => $s], array_values($items)), 60);
    }

    private function languages(array $lines): array
    {
        $levels = '/\b(?:native|fluent|fluently|basic|intermediate|advanced|proficient|proficiency|conversational|beginner|mother tongue|working|professional|full|limited|good|excellent|bilingual|spoken|written|elementary)\b/i';
        $items = [];
        foreach ($lines as $l) {
            if ($l === '') { continue; }
            $l = preg_replace('/^-\s*/', '', $l);
            $l = preg_replace('/\([^)]*\)/', '', $l);
            foreach (preg_split('/\s*[,;|\/\x{2022}\x{00B7}]\s*|\s+-\s+|\s+and\s+/iu', $l) as $s) {
                $s = trim(preg_replace($levels, '', $s), " .-:\t");
                if ($s === '' || mb_strlen($s) > 24 || count(preg_split('/\s+/', $s)) > 2 || !preg_match('/^[\p{L}][\p{L} \-]*$/u', $s)) { continue; }
                $items[mb_strtolower($s)] ??= mb_convert_case($s, MB_CASE_TITLE);
            }
        }

        return $this->cap(array_map(fn ($s) => ['language' => $s], array_values($items)), 15);
    }

    // ================================================================== certificates / training / seminars

    /** @return array<int,array{title:string,provider:?string,date:?string}> */
    private function titledItems(array $lines): array
    {
        $items = [];
        foreach ($this->entryLines($lines) as $line) {
            $line = trim(preg_replace('/^-\s*/', '', $line));
            if ($line === '' || mb_strlen($line) < 4) { continue; }
            $date = null;
            if ($r = $this->range($line)) { $date = $this->dbDate($r['end'] ?? $r['start']); $line = $r['rest']; }
            elseif (preg_match('/\(?\b(?:(?:' . self::MONTH_RE . ')\.?,?\s*)?((?:19|20)\d{2})\b\)?/i', $line, $m, PREG_OFFSET_CAPTURE)) {
                $date = $this->dbDate($this->parseDate($m[0][0]));
                $line = trim(substr($line, 0, $m[0][1]) . ' ' . substr($line, $m[0][1] + strlen($m[0][0])), " \t-|,;()");
            }
            $title = $provider = null;
            $parts = preg_split('/\s*(?:\||\x{2013}|\x{2014}| - |, | by | from | at | issued by )\s*/iu', $line, 2);
            $title = trim($parts[0] ?? '', " \t-|,;()");
            $provider = isset($parts[1]) ? trim($parts[1], " \t-|,;()") : null;
            if (preg_match('/^(.*?)\s*\(([^)]{3,80})\)$/u', $title, $pm) && !$provider) { [$title, $provider] = [trim($pm[1]), trim($pm[2])]; }
            if ($title === '' || mb_strlen($title) > 200) { continue; }
            $items[] = ['title' => $title, 'provider' => $provider ?: null, 'date' => $date];
        }

        return $items;
    }

    /** one line per item; a wrapped second line (starts in lower case) is joined to the line above */
    private function entryLines(array $lines): array
    {
        $out = [];
        foreach ($lines as $l) {
            if ($l === '') { continue; }
            if ($out && !$this->isBullet($l) && preg_match('/^\p{Ll}/u', $l)) { $out[count($out) - 1] .= ' ' . $l; continue; }
            $out[] = $l;
        }

        return $out;
    }

    private function certificates(array $lines): array
    {
        return $this->cap(array_map(fn ($i) => [
            'certificate_title' => mb_substr($i['title'], 0, 255), 'certificate_provider' => $i['provider'] ? mb_substr($i['provider'], 0, 255) : null,
            'date_completed' => $i['date'],
        ], $this->titledItems($lines)), 30);
    }

    /** @return array{0:array,1:array} training rows and seminar rows. Under "training and seminars", workshop-like lines are seminars. */
    private function trainingAndSeminars(array $training, array $seminar): array
    {
        $t = [];
        $s = [];
        foreach ($this->titledItems($training) as $i) {
            $isSeminar = (bool) preg_match('/\b(?:seminar|workshop|webinar|conference|forum|summit|symposium)\b/i', $i['title']);
            $row = $isSeminar
                ? ['seminar_title' => mb_substr($i['title'], 0, 255), 'seminar_provider' => $i['provider'] ? mb_substr($i['provider'], 0, 255) : null, 'date_completed' => $i['date']]
                : ['training_title' => mb_substr($i['title'], 0, 255), 'training_provider' => $i['provider'] ? mb_substr($i['provider'], 0, 255) : null, 'date_completed' => $i['date']];
            if ($isSeminar) { $s[] = $row; } else { $t[] = $row; }
        }
        foreach ($this->titledItems($seminar) as $i) {
            $s[] = ['seminar_title' => mb_substr($i['title'], 0, 255), 'seminar_provider' => $i['provider'] ? mb_substr($i['provider'], 0, 255) : null, 'date_completed' => $i['date']];
        }

        return [$this->cap($t, 30), $this->cap($s, 30)];
    }

    private function cap(array $rows, int $max): array
    {
        if (count($rows) > $max) { $this->warnings[] = "Only the first {$max} items of a long list were read."; }

        return array_slice(array_values($rows), 0, $max);
    }
}
