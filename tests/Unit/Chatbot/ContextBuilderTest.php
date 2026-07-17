<?php

namespace Tests\Unit\Chatbot;

use App\Models\Apartment;
use App\Models\Ownership;
use App\Models\Tower;
use App\Models\User;
use App\Services\Chatbot\ContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_includes_only_user_apartments(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $tower = Tower::factory()->create();
        $apt1 = Apartment::factory()->create(['tower_id' => $tower->id, 'code' => '101']);
        $apt2 = Apartment::factory()->create(['tower_id' => $tower->id, 'code' => '102']);

        Ownership::create(['user_id' => $user->id, 'apartment_id' => $apt1->id, 'role' => 'owner', 'active' => true]);
        Ownership::create(['user_id' => $other->id, 'apartment_id' => $apt2->id, 'role' => 'owner', 'active' => true]);

        $context = (new ContextBuilder())->build($user);

        $this->assertCount(1, $context['apartments']);
        $this->assertSame($apt1->id, $context['default_apartment_id']);
        $this->assertSame('101', $context['apartments'][0]['code']);
    }
}
