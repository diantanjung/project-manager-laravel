<?php

test('api health endpoint reports service status', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('data.environment', app()->environment())
        ->assertJsonStructure([
            'data' => [
                'status',
                'service',
                'environment',
                'timestamp',
            ],
        ]);
});
