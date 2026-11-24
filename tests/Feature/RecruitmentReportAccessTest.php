<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** GET /api/recruitment/reports (the Reports page): recruiters and both admin roles, nobody else. */
class RecruitmentReportAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function as(string $role): void
    {
        static $next = 895000;
        $code = $next += random_int(1, 30);
        $u = User::forceCreate(['fname' => 'r', 'lname' => 'T', 'fullname' => 'r T', 'role_code' => $role, 'email' => "r$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    public function test_a_def_admin_gets_an_empty_report_not_an_error(): void
    {
        $this->as('DEF-ADMIN');
        $r = $this->getJson('/api/recruitment/reports')->assertOk();
        $this->assertSame(true, $r->json('success'));
        $this->assertSame([], $r->json('jobs'), 'an admin has no vacancies of its own');
    }

    public function test_recruiters_and_the_master_admin_still_get_their_report(): void
    {
        foreach (['DEF-CLIENT', 'DEF-MASTERADMIN'] as $role) {
            $this->as($role);
            $this->getJson('/api/recruitment/reports')->assertOk()->assertJsonPath('success', true);
        }
    }

    public function test_job_seekers_and_guests_are_refused(): void
    {
        $this->as('DEF-USERS');
        $this->getJson('/api/recruitment/reports')->assertStatus(403);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/recruitment/reports')->assertStatus(401);
    }

    public function test_an_admin_cannot_report_on_someone_elses_job_posting(): void
    {
        $this->as('DEF-ADMIN');
        $this->getJson('/api/recruitment/reports?transNo=NOT-MINE')->assertStatus(403);
    }
}
