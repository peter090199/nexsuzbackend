<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Protected API routes must answer anonymous callers with a JSON 401, never a redirect to the POST-only /api/login. */
class ApiAuthResponseTest extends TestCase
{
    /** @return array<string, array{0:string}> */
    public static function protectedRoutes(): array
    {
        return [
            'email config' => ['/api/email-config'],
            'database admin' => ['/api/database-admin/server'],
            'subscription admin' => ['/api/subscription-admin/features'],
            'account plan list' => ['/api/account-plan/list'],
        ];
    }

    /** @dataProvider protectedRoutes */
    public function test_a_browser_style_request_without_a_token_gets_json_401_not_a_redirect(string $url): void
    {
        $r = $this->call('GET', $url, [], [], [], ['HTTP_ACCEPT' => 'text/html,application/xhtml+xml']);
        $this->assertSame(401, $r->getStatusCode(), "$url should be 401");
        $this->assertFalse($r->isRedirection());
        $this->assertStringContainsString('application/json', (string) $r->headers->get('Content-Type'));
        $this->assertFalse($r->json('success'));
        $this->assertStringContainsString('Unauthenticated', $r->json('message'));
    }

    /** @dataProvider protectedRoutes */
    public function test_a_json_request_without_a_token_gets_json_401(string $url): void
    {
        $this->getJson($url)->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_a_bad_token_is_also_401(): void
    {
        $this->getJson('/api/email-config', ['Authorization' => 'Bearer not-a-real-token'])->assertStatus(401);
    }

    public function test_public_api_routes_are_unaffected(): void
    {
        $this->getJson('/api/getPlanpublic')->assertOk();
        $this->getJson('/api/getActiveJobsByPublic')->assertOk();
    }
}
