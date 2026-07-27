<?php

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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

    getJson(URL::temporarySignedRoute('api.v1.attachments.download', now()->addMinutes(5), [
        'attachment' => $attachment->id,
    ]))
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
        ->assertJsonPath('data.uploader.id', $uploader->id)
        ->assertJsonPath('data.downloadUrl', fn (string $downloadUrl): bool => str_starts_with(
            $downloadUrl,
            url("/api/v1/attachments/{$attachment->id}/download?expires="),
        ));

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

test('deleting an uploaded attachment removes the stored object', function () {
    Storage::fake('r2');
    config(['filesystems.attachment_disk' => 'r2']);

    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);
    $file = UploadedFile::fake()->create('brief.pdf', 64, 'application/pdf');
    $path = 'attachments/'.$task->id.'/'.$file->hashName();

    $attachmentId = withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file' => $file,
        ])
        ->assertCreated()
        ->json('data.id');

    Storage::disk('r2')->assertExists($path);

    withToken($token)
        ->deleteJson("/api/v1/attachments/{$attachmentId}")
        ->assertNoContent();

    Storage::disk('r2')->assertMissing($path);
});

test('uploaded attachment download streams the stored object', function () {
    Storage::fake('r2');
    config(['filesystems.attachment_disk' => 'r2']);

    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);
    $file = UploadedFile::fake()->create('brief.pdf', 64, 'application/pdf');
    $path = 'attachments/'.$task->id.'/'.$file->hashName();

    $attachmentId = withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/attachments", [
            'file' => $file,
        ])
        ->assertCreated()
        ->json('data.id');

    getJson(URL::temporarySignedRoute('api.v1.attachments.download', now()->addMinutes(5), [
        'attachment' => $attachmentId,
    ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('attachment download requires a valid signed url', function () {
    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $attachment = Attachment::query()->create([
        'file_name' => 'brief.pdf',
        'file_url' => 'https://example.com/brief.pdf',
        'file_size' => 2048,
        'mime_type' => 'application/pdf',
        'task_id' => $task->id,
        'uploader_id' => $uploader->id,
    ]);

    getJson("/api/v1/attachments/{$attachment->id}/download")
        ->assertForbidden();
});
