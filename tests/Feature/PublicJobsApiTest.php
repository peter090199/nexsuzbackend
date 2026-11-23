<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** GET /api/getActiveJobsByPublic: public job list on the landing page. Read-only. */
class PublicJobsApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_is_public_and_returns_a_paginated_list(): void
    {
        $this->getJson('/api/getActiveJobsByPublic?page=1')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['current_page', 'last_page', 'per_page', 'total', 'next_page_url',
                'data' => [['job_id', 'transNo', 'job_name', 'job_position', 'job_image', 'location', 'work_type', 'company']]]]);
    }

    public function test_page_size_is_clamped(): void
    {
        $this->getJson('/api/getActiveJobsByPublic?per_page=1000000')->assertOk()->assertJsonPath('data.per_page', 50);
        $this->getJson('/api/getActiveJobsByPublic?per_page=0')->assertOk()->assertJsonPath('data.per_page', 1);
        $this->getJson('/api/getActiveJobsByPublic?per_page=abc')->assertOk()->assertJsonPath('data.per_page', 1);
        $this->getJson('/api/getActiveJobsByPublic')->assertOk()->assertJsonPath('data.per_page', 20);
    }

    public function test_only_active_jobs_that_are_still_open_are_listed(): void
    {
        $ids = collect($this->getJson('/api/getActiveJobsByPublic?per_page=50')->assertOk()->json('data.data'))->pluck('job_id');

        $listed = DB::table('jobposting')->whereIn('job_id', $ids)->get();
        $this->assertSame(0, $listed->where('recordstatus', '!=', 'active')->count());
        $this->assertSame(0, $listed->filter(fn ($j) => $j->application_deadline && $j->application_deadline < now()->toDateString())->count());

        // and nothing open + active is missing from the list
        $expected = DB::table('jobposting')->where('recordstatus', 'active')
            ->where(fn ($q) => $q->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', now()->toDateString()))
            ->count();
        $this->assertSame($expected, $ids->count());
    }
}
