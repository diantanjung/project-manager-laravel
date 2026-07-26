<?php

use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('webhook endpoints and deliveries can be managed through the api', function () {
    $user = User::factory()->admin()->create();
    $token = domainApiAccessToken($user);

    $endpointId = withToken($token)
        ->postJson('/api/v1/webhook-endpoints', [
            'name' => 'Task Events',
            'url' => 'https://example.com/webhooks',
            'secret' => 'secret-value',
            'events' => ['task.created', 'task.updated'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Task Events')
        ->json('data.id');

    $endpoint = WebhookEndpoint::query()->findOrFail($endpointId);

    withToken($token);

    getJson('/api/v1/webhook-endpoints')
        ->assertOk()
        ->assertJsonPath('data.0.id', $endpoint->id);

    withToken($token)
        ->patchJson("/api/v1/webhook-endpoints/{$endpoint->id}", [
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.isActive', false);

    WebhookDelivery::query()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_type' => 'task.created',
        'payload' => ['task_id' => 1],
        'status' => 'delivered',
        'attempt_count' => 1,
        'delivered_at' => now(),
    ]);

    withToken($token);

    getJson('/api/v1/webhook-deliveries')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.eventType', 'task.created');

    withToken($token)
        ->deleteJson("/api/v1/webhook-endpoints/{$endpoint->id}")
        ->assertNoContent();

    assertModelMissing($endpoint);
});
