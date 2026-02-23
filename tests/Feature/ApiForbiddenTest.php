<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ApiForbiddenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_standard_structure_and_translated_message_for_forbidden()
    {
        // Locale en español para verificar traducción
        app()->setLocale('es');
        $user = User::factory()->create(); // Sin roles
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/accounts'); // Endpoint exige rol
        $response->assertStatus(403);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
        $this->assertEquals('FORBIDDEN', $response->json('error.code'));
        $this->assertEquals(__('errors.FORBIDDEN'), $response->json('error.message'));
    }
}
