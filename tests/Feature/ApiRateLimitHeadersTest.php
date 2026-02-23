<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ApiRateLimitHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_rate_limit_headers_on_successful_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/invoices');
        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
        $response->assertHeader('X-RateLimit-Policy');
    }
}
