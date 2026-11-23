<?php

namespace Tests\Feature;

use App\Helpers\NotificationPrefs;
use App\Models\User;
use App\Services\Recruitment\RecruitmentPipeline;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings > Email Preferences > "Job application updates":
 * ON  -> the applicant is emailed for received / review / interview / approved / rejected
 * OFF -> no email, but the decision itself and the in-app message are not affected.
 * Uses the "array" mail driver (nothing leaves the machine) and rolled-back transactions.
 */
class ApplicationEmailPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    private User $applicant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        Mail::purge('array');

        $code = random_int(990000, 999000);
        $this->applicant = User::forceCreate([
            'fname' => 'Ana', 'lname' => 'Applicant', 'fullname' => 'Ana Applicant',
            'email' => "ana{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function application(): object
    {
        return (object) [
            'code' => $this->applicant->code, 'email' => $this->applicant->email, 'fullname' => 'Ana Applicant',
            'job_name' => 'Laravel Developer', 'job' => (object) ['company' => 'Acme', 'code' => 1],
            'applied_status' => 'review',
        ];
    }

    private function sentMails(): int
    {
        return count(app('mailer')->getSymfonyTransport()->messages());
    }

    public static function events(): array
    {
        return [['received'], ['review'], ['interview'], ['approved'], ['rejected']];
    }

    public function test_the_switch_defaults_to_on(): void
    {
        $this->assertTrue(NotificationPrefs::allows($this->applicant->id, 'email_application_updates'));
    }

    #[DataProvider('events')]
    public function test_email_is_sent_when_the_switch_is_on(string $event): void
    {
        RecruitmentPipeline::notify($this->application(), $event, null);

        $this->assertSame(1, $this->sentMails());
    }

    #[DataProvider('events')]
    public function test_no_email_is_sent_when_the_switch_is_off(string $event): void
    {
        NotificationPrefs::update($this->applicant->id, ['email_application_updates' => false]);

        RecruitmentPipeline::notify($this->application(), $event, null);

        $this->assertSame(0, $this->sentMails());
    }

    public function test_turning_it_back_on_sends_again(): void
    {
        NotificationPrefs::update($this->applicant->id, ['email_application_updates' => false]);
        RecruitmentPipeline::notify($this->application(), 'review', null);
        NotificationPrefs::update($this->applicant->id, ['email_application_updates' => true]);
        RecruitmentPipeline::notify($this->application(), 'approved', null);

        $this->assertSame(1, $this->sentMails());
    }

    public function test_an_applicant_without_an_account_is_still_emailed(): void
    {
        $app = $this->application();
        $app->code = 'no-such-account';

        RecruitmentPipeline::notify($app, 'received', null);

        $this->assertSame(1, $this->sentMails());
    }

    public function test_restore_defaults_on_notifications_keeps_the_email_choice(): void
    {
        NotificationPrefs::update($this->applicant->id, ['email_application_updates' => false, 'sound' => true]);

        NotificationPrefs::reset($this->applicant->id);

        $this->assertFalse(NotificationPrefs::allows($this->applicant->id, 'email_application_updates'));
        $this->assertFalse(NotificationPrefs::allows($this->applicant->id, 'sound'));
    }
}
