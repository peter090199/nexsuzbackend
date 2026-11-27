<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Writes only app_settings, users and security_audit_logs, which are InnoDB and roll back. */
class CvPdfSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::forget('cv.pdf');
    }

    private function as(?string $role): void
    {
        $this->app['auth']->forgetGuards();
        if (!$role) { return; }
        static $next = 877500;
        $code = $next += random_int(2, 20);
        Sanctum::actingAs(User::forceCreate(['fname' => 'C', 'lname' => 'V', 'fullname' => 'C V', 'role_code' => $role,
            'email' => "cvpdf{$code}@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']));
    }

    private function settings(array $over = []): array
    {
        return $over + ['preview_enabled' => true, 'method' => 'preview', 'display' => 'tab', 'filename' => 'Resume {name}',
            'page_size' => 'letter', 'orientation' => 'landscape', 'allow_print' => false, 'allow_download' => true];
    }

    public function test_anyone_reads_the_options_with_defaults_that_keep_the_old_behaviour(): void
    {
        $this->getJson('/api/cv/pdf-settings')->assertOk()->assertJsonPath('data.page_size', 'a4')->assertJsonPath('data.orientation', 'portrait')
            ->assertJsonPath('data.allow_print', true)->assertJsonPath('data.allow_download', true)->assertJsonPath('data.filename', 'CV_{name}')
            ->assertJsonMissingPath('data.app_url');
    }

    public function test_only_the_master_admin_changes_them_or_sees_the_server_details(): void
    {
        foreach ([[null, 401], ['DEF-USERS', 403], ['DEF-CLIENT', 403], ['DEF-ADMIN', 403]] as [$role, $status]) {
            $this->as($role);
            $this->putJson('/api/cv/pdf-settings', $this->settings())->assertStatus($status);
            $this->getJson('/api/cv/pdf-environment')->assertStatus($status);
        }
        $this->assertNull(AppSetting::read('cv.pdf'));
    }

    public function test_settings_are_saved_audited_and_read_back_by_everyone(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->putJson('/api/cv/pdf-settings', $this->settings())->assertOk()->assertJsonPath('data.filename', 'Resume {name}');
        $this->assertTrue(DB::table('security_audit_logs')->where('action', 'cv.pdf_settings')->exists());

        $this->as(null);
        $this->getJson('/api/cv/pdf-settings')->assertJsonPath('data.page_size', 'letter')->assertJsonPath('data.allow_print', false);
    }

    public function test_bad_values_are_refused(): void
    {
        $this->as('DEF-MASTERADMIN');
        foreach ([['filename' => '../etc/passwd'], ['filename' => 'a:b'], ['filename' => ''], ['page_size' => 'a3'],
            ['orientation' => 'sideways'], ['display' => 'popup'], ['method' => 'download', 'allow_download' => false]] as $bad) {
            $this->putJson('/api/cv/pdf-settings', $this->settings($bad))->assertStatus(422);
        }
    }

    public function test_the_environment_report_follows_the_server_configuration(): void
    {
        $this->as('DEF-MASTERADMIN');
        config(['app.url' => 'http://localhost', 'filesystems.disks.public.url' => 'http://localhost/storage']);
        $this->getJson('/api/cv/pdf-environment')->assertOk()->assertJsonPath('data.environment', 'local')
            ->assertJsonPath('data.storage_url', 'http://localhost/storage')->assertJsonStructure(['data' => ['storage_linked', 'storage_writable', 'warnings']]);

        $this->app['env'] = 'production';
        config(['app.url' => 'https://api.example.test', 'app.debug' => true]);
        $report = $this->getJson('/api/cv/pdf-environment')->assertJsonPath('data.environment', 'production')->json('data.warnings');
        $this->assertStringContainsString('APP_URL', implode(' ', $report));
        $this->assertStringContainsString('APP_DEBUG', implode(' ', $report));
    }
}
