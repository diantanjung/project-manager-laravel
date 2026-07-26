<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('task comments and attachments can be accessed through nested endpoints', function () {
    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/comments", [
            'content' => 'Nested comment.',
            'author_id' => $author->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.content', 'Nested comment.');

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file_name' => 'nested.pdf',
            'file_url' => 'https://example.com/nested.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploader_id' => $author->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.fileName', 'nested.pdf');

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.content', 'Nested comment.');

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}/attachments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.fileName', 'nested.pdf');
});

test('users can be assigned to tasks through assignment endpoints', function () {
    $assigner = User::factory()->create();
    $member = User::factory()->create();
    $task = domainApiTask(creator: $assigner);
    $token = domainApiAccessToken($assigner);

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/assignments", [
            'user_id' => $member->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $member->id)
        ->assertJsonPath('data.taskAssignment.assignedBy', $assigner->id);

    expect($task->assignedUsers()->whereKey($member->id)->exists())->toBeTrue();

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/assignments", [
            'user_id' => $member->id,
        ])
        ->assertConflict()
        ->assertJsonPath('message', 'User is already assigned to this task.');

    withToken($token)
        ->deleteJson("/api/v1/tasks/{$task->id}/assignments/{$member->id}")
        ->assertNoContent();

    expect($task->assignedUsers()->whereKey($member->id)->exists())->toBeFalse();
});
