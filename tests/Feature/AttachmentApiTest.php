<?php

use App\Models\Attachment;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('attachment download redirects to the stored file url', function () {
    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);
    $attachment = Attachment::query()->create([
        'file_name' => 'brief.pdf',
        'file_url' => 'https://example.com/brief.pdf',
        'file_size' => 2048,
        'mime_type' => 'application/pdf',
        'task_id' => $task->id,
        'uploader_id' => $uploader->id,
    ]);

    withToken($token);

    getJson("/api/v1/attachments/{$attachment->id}/download")
        ->assertRedirect('https://example.com/brief.pdf');
});

test('attachments can be managed through the api', function () {
    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);

    $attachmentId = withToken($token)
        ->postJson('/api/v1/attachments', [
            'file_name' => 'brief.pdf',
            'file_url' => 'https://example.com/brief.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'task_id' => $task->id,
            'uploader_id' => $uploader->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.fileName', 'brief.pdf')
        ->json('data.id');

    $attachment = Attachment::query()->findOrFail($attachmentId);

    withToken($token);

    getJson("/api/v1/attachments/{$attachment->id}")
        ->assertOk()
        ->assertJsonPath('data.uploader.id', $uploader->id);

    withToken($token)
        ->patchJson("/api/v1/attachments/{$attachment->id}", [
            'file_size' => 4096,
        ])
        ->assertOk()
        ->assertJsonPath('data.fileSize', 4096);

    withToken($token)
        ->deleteJson("/api/v1/attachments/{$attachment->id}")
        ->assertNoContent();

    assertModelMissing($attachment);
});
