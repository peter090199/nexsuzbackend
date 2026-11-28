<?php

namespace Tests\Feature;

use App\Http\Controllers\System\PublicStatsController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Read-only: nothing is written to the database. */
class PublicStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(PublicStatsController::CACHE_KEY);
    }

    public function test_guests_get_the_real_totals_and_nothing_else(): void
    {
        $res = $this->getJson('/api/public/stats')->assertOk()->assertJsonPath('success', true);

        $this->assertSame([
            'professionals' => DB::table('users')->where('role_code', 'DEF-USERS')->where('status', 'A')->count(),
            'jobs'          => DB::table('jobposting')->count(),
            'companies'     => DB::table('users')->where('role_code', 'DEF-CLIENT')->where('status', 'A')->count(),
        ], $res->json('data'));
    }

    public function test_totals_are_cached(): void
    {
        $this->getJson('/api/public/stats')->assertOk();
        $this->assertTrue(Cache::has(PublicStatsController::CACHE_KEY));
    }
}
