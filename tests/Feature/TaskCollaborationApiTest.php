<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('task comments and attachments can be accessed through nested endpoints', function () {
    Storage::fake('r2');
    config(['filesystems.attachment_disk' => 'r2']);

    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);
    $file = UploadedFile::fake()->create('nested.pdf', 1, 'application/pdf');

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
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.originalName', 'nested.pdf');

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.content', 'Nested comment.');

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}/attachments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.originalName', 'nested.pdf');
});

test('task attachment endpoint rejects metadata urls instead of uploading files', function () {
    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file_name' => 'banner_1.png',
            'file_url' => 'http://localhost:5173/uploads/banner_1.png',
            'file_size' => 1024,
            'mime_type' => 'image/png',
            'uploader_id' => $author->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file', 'file_url']);
});

test('task attachments can be uploaded to the configured attachment disk', function () {
    Storage::fake('r2');
    config(['filesystems.attachment_disk' => 'r2']);

    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);
    $file = UploadedFile::fake()->create('brief.pdf', 128, 'application/pdf');
    $expectedPath = 'attachments/'.$task->id.'/'.$file->hashName();

    withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.uploaderId', $uploader->id)
        ->assertJsonPath('data.disk', 'r2')
        ->assertJsonPath('data.path', $expectedPath)
        ->assertJsonPath('data.originalName', 'brief.pdf')
        ->assertJsonPath('data.mimeType', 'application/pdf')
        ->assertJsonPath('data.size', 128 * 1024);

    Storage::disk('r2')->assertExists($expectedPath);
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
