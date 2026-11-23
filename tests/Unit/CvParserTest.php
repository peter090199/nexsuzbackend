<?php

namespace Tests\Unit;

use App\Services\CvImport\CvParser;
use PHPUnit\Framework\TestCase;

/**
 * CvParser turns the text of a CV into profile data. These tests pin down what it reads, how it maps it to the
 * profile fields, and above all that it never invents anything the text does not say.
 */
class CvParserTest extends TestCase
{
    private function parse(string $text): array
    {
        return (new CvParser())->parse($text);
    }

    private function standardCv(): string
    {
        return <<<'TXT'
PEDRO YORPO
Software Engineer
pedro.yorpo@gmail.com | +63 917 123 4567 | Quezon City, Metro Manila, Philippines

PROFESSIONAL SUMMARY
Software engineer with 6 years of experience building web applications with Laravel and Angular.

WORK EXPERIENCE
Senior Software Engineer - Acme Technologies Inc.
Jan 2021 - Present
- Led a team of five engineers building a recruitment platform
- Reduced page load time by 40%

Software Developer, Globex Corporation
June 2018 - December 2020
- Built REST APIs with Laravel

EDUCATION
Bachelor of Science in Information Technology
Ateneo de Manila University
2012 - 2016

Senior High School - STEM
Quezon City Science High School
2010 - 2012

SKILLS
PHP, Laravel, Angular, TypeScript, MySQL
- Git
- Docker

LANGUAGES
English (Fluent), Filipino (Native)

CERTIFICATIONS
AWS Certified Developer - Amazon Web Services (2022)
Laravel Certified Developer, Laravel Inc. 2020

TRAININGS AND SEMINARS
Advanced Angular Workshop - Angular Philippines, March 2023
Secure Coding Training | Cyber Academy | 2021

PERSONAL INFORMATION
Date of Birth: August 29, 1995
Permanent Address: Bulacan, Philippines

REFERENCES
Available upon request.
TXT;
    }

    // ------------------------------------------------------------------ personal information

    public function test_it_maps_the_personal_information(): void
    {
        $p = $this->parse($this->standardCv())['profile'];

        $this->assertSame('Pedro', $p['fname']);
        $this->assertSame('Yorpo', $p['lname']);
        $this->assertSame('pedro.yorpo@gmail.com', $p['email']);
        $this->assertSame('+63 917 123 4567', $p['contact_no']);
        $this->assertSame('Software Engineer', $p['profession']);
        $this->assertStringStartsWith('Software engineer with 6 years', $p['summary']);
        $this->assertSame('1995-08-29', $p['date_birth']);
        $this->assertSame('Philippines', $p['home_country']);
        $this->assertSame('Quezon City', $p['current_location']);
        $this->assertSame('Metro Manila', $p['current_state']);
        $this->assertSame('Bulacan', $p['home_state'], 'the permanent address is the home state');
    }

    public function test_the_current_address_is_not_confused_with_the_permanent_one(): void
    {
        $p = $this->parse("JUAN DELA CRUZ\nPresent Address: Cebu City, Cebu, Philippines\nPermanent Address: Laoag City, Ilocos Norte, Philippines\n")['profile'];

        $this->assertSame('Cebu City', $p['current_location']);
        $this->assertSame('Cebu', $p['current_state']);
        $this->assertSame('Ilocos Norte', $p['home_state']);
        $this->assertSame('Philippines', $p['home_country']);
    }

    public function test_a_labelled_name_and_last_name_first_are_understood(): void
    {
        $p = $this->parse("CURRICULUM VITAE\n\nPERSONAL INFORMATION\nName: Santos, Maria Clara\nEmail: maria@example.com\nMobile: 0917-555-1234\n")['profile'];

        $this->assertSame('Maria', $p['fname']);
        $this->assertSame('Santos', $p['lname']);
        $this->assertSame('maria@example.com', $p['email']);
        $this->assertSame('0917-555-1234', $p['contact_no']);
    }

    public function test_credentials_after_a_name_are_not_part_of_it(): void
    {
        $p = $this->parse("John Smith, MBA\nFinance Manager\njohn@example.com\n")['profile'];

        $this->assertSame('John', $p['fname']);
        $this->assertSame('Smith', $p['lname']);
        $this->assertSame('Finance Manager', $p['profession']);
    }

    public function test_a_job_title_on_the_first_line_is_not_taken_for_a_name(): void
    {
        $p = $this->parse("Software Engineer\nMaria Reyes\nmaria@example.com\n")['profile'];

        $this->assertSame('Maria', $p['fname']);
        $this->assertSame('Reyes', $p['lname']);
    }

    public function test_an_unambiguous_slash_date_of_birth_is_read(): void
    {
        $this->assertSame('1992-12-25', $this->parse("Ana Lopez\nDate of Birth: 12/25/1992\n")['profile']['date_birth']);
        $this->assertSame('1992-12-25', $this->parse("Ana Lopez\nBirthday: 25 December 1992\n")['profile']['date_birth']);
    }

    public function test_an_ambiguous_date_of_birth_is_left_empty_and_reported(): void
    {
        $r = $this->parse("Ana Lopez\nDate of Birth: 03/04/1992\n");

        $this->assertNull($r['profile']['date_birth'], 'day and month cannot be told apart: it must not be guessed');
        $this->assertNotEmpty(array_filter($r['warnings'], fn ($w) => str_contains($w, 'date of birth')));
    }

    public function test_an_impossible_date_of_birth_is_dropped(): void
    {
        $this->assertNull($this->parse("Ana Lopez\nDate of Birth: 31 February 1992\n")['profile']['date_birth']);
        $this->assertNull($this->parse("Ana Lopez\nDate of Birth: January 5, " . (date('Y') - 3) . "\n")['profile']['date_birth'], 'a 3 year old is not an applicant');
    }

    public function test_a_year_range_is_not_a_phone_number(): void
    {
        $this->assertNull($this->parse("Ana Lopez\nWorked 2015 - 2020 at a company\n")['profile']['contact_no']);
    }

    public function test_a_long_summary_is_shortened_and_reported(): void
    {
        $long = str_repeat('Experienced professional with a track record of delivering results. ', 8);
        $r = $this->parse("Ana Lopez\n\nSUMMARY\n{$long}\n");

        $this->assertLessThanOrEqual(255, mb_strlen($r['profile']['summary']));
        $this->assertNotEmpty(array_filter($r['warnings'], fn ($w) => str_contains($w, '255')));
    }

    // ------------------------------------------------------------------ sections

    public function test_education_is_split_into_entries(): void
    {
        $e = $this->parse($this->standardCv())['education'];

        $this->assertCount(2, $e);
        $this->assertSame('Bachelor of Science in Information Technology', $e[0]['highest_education']);
        $this->assertSame('Ateneo de Manila University', $e[0]['school_name']);
        $this->assertSame([2012, 2016, 'graduate'], [$e[0]['start_year'], $e[0]['end_year'], $e[0]['status']]);
        $this->assertSame('Senior High School - STEM', $e[1]['highest_education']);
        $this->assertSame('Quezon City Science High School', $e[1]['school_name']);
    }

    public function test_an_ongoing_degree_has_no_end_year(): void
    {
        $e = $this->parse("Ana Lopez\n\nEDUCATION\nMaster of Science in Computer Science\nUniversity of the Philippines\nAugust 2022 - Present\n")['education'];

        $this->assertCount(1, $e);
        $this->assertSame('ongoing', $e[0]['status']);
        $this->assertNull($e[0]['end_year']);
        $this->assertSame([2022, 'August'], [$e[0]['start_year'], $e[0]['start_month']]);
    }

    public function test_education_without_blank_lines_is_still_split(): void
    {
        $e = $this->parse("Ana Lopez\n\nEDUCATION\nBS Accountancy, Polytechnic University of the Philippines 2008 - 2012\nSenior High School, Rizal High School 2004 - 2008\n")['education'];

        $this->assertCount(2, $e);
        $this->assertSame(2012, $e[0]['end_year']);
        $this->assertSame(2008, $e[1]['end_year']);
    }

    public function test_work_experience_is_split_and_the_roles_are_told_apart(): void
    {
        $j = $this->parse($this->standardCv())['employment'];

        $this->assertCount(2, $j);
        $this->assertSame(['Senior Software Engineer', 'Acme Technologies Inc.', null], [$j[0]['position'], $j[0]['company_name'], $j[0]['date_completed']], 'still employed: no end date');
        $this->assertStringContainsString('Reduced page load time by 40%', $j[0]['job_description']);
        $this->assertSame(['Software Developer', 'Globex Corporation', '2020-12-01'], [$j[1]['position'], $j[1]['company_name'], $j[1]['date_completed']]);
    }

    public function test_a_job_on_one_line_with_the_dates_inside(): void
    {
        $j = $this->parse("Ana Lopez\n\nEXPERIENCE\nAccounting Assistant at Sunrise Trading Co. (2016 - 2019)\n- Prepared monthly reports\n")['employment'];

        $this->assertCount(1, $j);
        $this->assertSame('Accounting Assistant', $j[0]['position']);
        $this->assertSame('Sunrise Trading Co.', $j[0]['company_name']);
    }

    public function test_skills_are_split_cleaned_and_not_repeated(): void
    {
        $s = array_column($this->parse("Ana Lopez\n\nSKILLS\nPHP, Laravel, php, MySQL\n- Git\n- Docker\nTechnical Skills: Vue, laravel\n")['skills'], 'skills');

        $this->assertSame(['PHP', 'Laravel', 'MySQL', 'Git', 'Docker', 'Vue'], $s);
    }

    public function test_inline_skills_after_the_heading_are_read(): void
    {
        $s = array_column($this->parse("Ana Lopez\n\nSkills: Excel, Bookkeeping, Payroll\n")['skills'], 'skills');

        $this->assertSame(['Excel', 'Bookkeeping', 'Payroll'], $s);
    }

    public function test_languages_lose_their_proficiency_notes(): void
    {
        $l = array_column($this->parse($this->standardCv())['languages'], 'language');

        $this->assertSame(['English', 'Filipino'], $l);
    }

    public function test_certificates_training_and_seminars(): void
    {
        $r = $this->parse($this->standardCv());

        $this->assertSame(
            [['AWS Certified Developer', 'Amazon Web Services', '2022-01-01'], ['Laravel Certified Developer', 'Laravel Inc.', '2020-01-01']],
            array_map(fn ($c) => [$c['certificate_title'], $c['certificate_provider'], $c['date_completed']], $r['certificate'])
        );
        $this->assertSame([['Secure Coding Training', 'Cyber Academy', '2021-01-01']], array_map(fn ($t) => [$t['training_title'], $t['training_provider'], $t['date_completed']], $r['training']));
        $this->assertSame([['Advanced Angular Workshop', 'Angular Philippines', '2023-03-01']], array_map(fn ($t) => [$t['seminar_title'], $t['seminar_provider'], $t['date_completed']], $r['seminar']), 'a workshop under "trainings and seminars" is a seminar');
    }

    public function test_references_and_hobbies_are_not_imported(): void
    {
        $r = $this->parse("Ana Lopez\n\nSKILLS\nExcel\n\nHOBBIES\nSwimming, Reading\n\nREFERENCES\nJohn Cruz, CEO, 0917 111 2222\n");

        $this->assertSame(['Excel'], array_column($r['skills'], 'skills'));
    }

    // ------------------------------------------------------------------ never invent

    public function test_text_with_nothing_recognisable_gives_empty_results_and_warnings(): void
    {
        $r = $this->parse("lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor\n");

        foreach ($r['profile'] as $field => $value) {
            $this->assertNull($value, "{$field} must stay empty, nothing in the text says what it is");
        }
        foreach (['education', 'languages', 'skills', 'training', 'seminar', 'employment', 'certificate'] as $section) {
            $this->assertSame([], $r[$section]);
        }
        $this->assertNotEmpty($r['warnings']);
    }

    public function test_the_year_alone_is_all_a_date_claims_and_a_future_year_is_dropped(): void
    {
        $future = (int) date('Y') + 3;
        $r = $this->parse("Ana Lopez\n\nCERTIFICATIONS\nFood Safety Certificate - City Health Office ({$future})\n");

        $this->assertSame('Food Safety Certificate', $r['certificate'][0]['certificate_title']);
        $this->assertNull($r['certificate'][0]['date_completed'], 'a date in the future is not a completion date');
    }

    public function test_the_parser_is_repeatable(): void
    {
        $this->assertSame($this->parse($this->standardCv()), $this->parse($this->standardCv()));
    }
}
