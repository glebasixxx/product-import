<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_public_and_reports_checks(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.queue.status', 'ok')
            ->assertJsonPath('checks.storage.status', 'ok');
    }

    public function test_health_endpoint_returns_503_when_broker_is_down(): void
    {
        config([
            'queue.default' => 'rabbitmq',
            'queue.connections.rabbitmq.hosts.0.host' => '127.0.0.1',
            'queue.connections.rabbitmq.hosts.0.port' => 1,
        ]);

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'fail')
            ->assertJsonPath('checks.queue.status', 'fail');
    }
}
