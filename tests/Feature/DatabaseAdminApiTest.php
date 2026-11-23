<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseAdminService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Master-admin database tools. Read-only against the real database, except the backup test which writes (and removes)
 * one file under storage/app/backups. OPTIMIZE/ANALYZE are only tested for their validation: they are DDL-like and
 * would commit the test transaction.
 */
class DatabaseAdminApiTest extends TestCase
{
    use DatabaseTransactions;

    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $f) { @unlink(DatabaseAdminService::backupDir() . DIRECTORY_SEPARATOR . $f); }
        parent::tearDown();
    }

    private function as(string $role): void
    {
        static $next = 895000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'x', 'lname' => 'Tester', 'fullname' => 'x Tester', 'role_code' => $role,
            'email' => "x$code@example.test", 'password' => bcrypt('secret-pass'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    public function test_only_master_admin_is_allowed(): void
    {
        foreach (['DEF-ADMIN', 'DEF-USERS', 'DEF-CLIENT'] as $role) {
            $this->as($role);
            foreach (['server', 'storage', 'tables', 'backups'] as $p) {
                $this->getJson("/api/database-admin/$p")->assertStatus(403);
            }
            $this->postJson('/api/database-admin/backups')->assertStatus(403);
            $this->postJson('/api/database-admin/tables/optimize', ['tables' => ['users']])->assertStatus(403);
        }
    }

    public function test_server_storage_and_tables_report_real_data(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->getJson('/api/database-admin/server')->assertOk()
            ->assertJsonStructure(['data' => ['database' => ['name', 'version', 'latency_ms'], 'app' => ['laravel', 'php'], 'server' => ['os', 'extensions']]]);
        $this->getJson('/api/database-admin/storage')->assertOk()
            ->assertJsonStructure(['data' => ['disk' => ['total', 'free', 'used', 'used_percent'], 'database_bytes', 'folders']]);
        $tables = $this->getJson('/api/database-admin/tables')->assertOk()->json('data');
        $this->assertContains('users', array_column($tables, 'name'));
        $this->assertArrayHasKey('total_bytes', $tables[0]);
    }

    public function test_maintenance_only_accepts_real_table_names(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->postJson('/api/database-admin/tables/optimize', [])->assertStatus(422);
        $this->postJson('/api/database-admin/tables/optimize', ['tables' => ['users; DROP TABLE users']])->assertStatus(422)
            ->assertJsonPath('message', 'Unknown table: users; DROP TABLE users');
        $this->postJson('/api/database-admin/tables/optimize', ['tables' => ['nope_nope']])->assertStatus(422);
        $this->postJson('/api/database-admin/tables/vacuum', ['tables' => ['users']])->assertStatus(404);
        $this->postJson('/api/database-admin/tables/truncate', ['tables' => ['users']])->assertStatus(404);
    }

    public function test_backup_create_list_and_download(): void
    {
        $this->as('DEF-MASTERADMIN');
        $b = $this->postJson('/api/database-admin/backups')->assertStatus(201)->json('data');
        $this->created[] = $b['name'];
        $this->assertMatchesRegularExpression('/^db-\d{8}-\d{6}-[a-z0-9]{4}\.sql\.gz$/', $b['name']);
        $this->assertGreaterThan(100, $b['bytes']);

        $this->assertContains($b['name'], array_column($this->getJson('/api/database-admin/backups')->assertOk()->json('data'), 'name'));

        $sql = gzdecode(file_get_contents(DatabaseAdminService::backupDir() . DIRECTORY_SEPARATOR . $b['name']));
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('INSERT INTO `userplanheader`', $sql);
        $this->assertStringContainsString('-- End of backup', $sql);

        $this->get("/api/database-admin/backups/{$b['name']}/download", ['Authorization' => 'Bearer x'])->assertOk()->assertHeader('content-type', 'application/gzip');
    }

    public function test_download_refuses_anything_but_our_own_backup_names(): void
    {
        $this->as('DEF-MASTERADMIN');
        foreach (['..%2F..%2F.env', 'passwd', 'db-1.sql.gz', 'db-20260101-000000.sql', 'db-20990101-000000.sql.gz'] as $bad) {
            $this->getJson("/api/database-admin/backups/$bad/download")->assertStatus(404);
        }
    }
}
