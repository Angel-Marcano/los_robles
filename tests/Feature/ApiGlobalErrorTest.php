<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ApiGlobalErrorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_wraps_not_found_errors_in_standard_structure()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/inexistent-endpoint-xyz');
        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
        $this->assertEquals('NOT_FOUND', $response->json('error.code'));
    }
}
