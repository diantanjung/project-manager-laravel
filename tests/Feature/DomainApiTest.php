<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\AuthTokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function domainApiAccessToken(?User $user = null): string
{
    $user ??= User::factory()->create();

    return app(AuthTokenService::class)->issueTokenPair($user)['accessToken'];
}

function domainApiProject(?User $owner = null): Project
{
    $owner ??= User::factory()->create();

    return Project::query()->create([
        'name' => 'Website redesign',
        'description' => 'Refresh the marketing site',
        'owner_id' => $owner->id,
    ]);
}

function domainApiTask(?Project $project = null, ?User $creator = null, ?User $assignee = null): Task
{
    $creator ??= User::factory()->create();
    $assignee ??= User::factory()->create();
    $project ??= domainApiProject($creator);

    return Task::withoutEvents(fn (): Task => Task::query()->create([
        'title' => 'Create wireframes',
        'description' => 'Initial homepage wireframes',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $assignee->id,
        'due_date' => '2026-08-01',
    ]));
}

test('domain api routes require an access token', function () {
    $this->getJson('/api/v1/projects')->assertUnauthorized();
});

test('projects can be managed through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);

    $createdProjectId = $this->withToken($token)
        ->postJson('/api/v1/projects', [
            'name' => 'Mobile app',
            'description' => 'Version two delivery',
            'owner_id' => $owner->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mobile app')
        ->assertJsonPath('data.ownerId', $owner->id)
        ->json('data.id');

    $project = Project::query()->findOrFail($createdProjectId);
    $this->assertModelExists($project);

    $this->withToken($token)
        ->getJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.owner.id', $owner->id);

    $this->withToken($token)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Mobile app launch',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Mobile app launch');

    expect($project->refresh()->name)->toBe('Mobile app launch');

    $this->withToken($token)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertNoContent();

    $this->assertModelMissing($project);
});

test('teams can be managed through the api', function () {
    $token = domainApiAccessToken();

    $teamId = $this->withToken($token)
        ->postJson('/api/v1/teams', [
            'name' => 'Engineering',
            'description' => 'Product engineering',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Engineering')
        ->json('data.id');

    $team = Team::query()->findOrFail($teamId);

    $this->withToken($token)
        ->getJson('/api/v1/teams')
        ->assertOk()
        ->assertJsonPath('data.0.id', $team->id);

    $this->withToken($token)
        ->patchJson("/api/v1/teams/{$team->id}", [
            'description' => 'Platform engineering',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Platform engineering');

    $this->withToken($token)
        ->deleteJson("/api/v1/teams/{$team->id}")
        ->assertNoContent();

    $this->assertModelMissing($team);
});

test('users can be managed through the api', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    $userId = $this->withToken($token)
        ->postJson('/api/v1/users', [
            'name' => 'Dian Project Manager',
            'email' => 'dian.pm@example.com',
            'password' => 'password',
            'role' => UserRole::ProjectManager->value,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Dian Project Manager')
        ->assertJsonPath('data.role', UserRole::ProjectManager->value)
        ->json('data.id');

    $user = User::query()->findOrFail($userId);
    $this->assertModelExists($user);

    $this->withToken($token)
        ->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $user->id,
            'email' => 'dian.pm@example.com',
        ]);

    $this->withToken($token)
        ->getJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'dian.pm@example.com');

    $this->withToken($token)
        ->patchJson("/api/v1/users/{$user->id}", [
            'name' => 'Dian Delivery Lead',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Dian Delivery Lead')
        ->assertJsonPath('data.isActive', false);

    expect($user->refresh()->name)->toBe('Dian Delivery Lead')
        ->and($user->is_active)->toBeFalse();

    $this->withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar_url' => 'https://example.com/avatar.jpg',
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', 'https://example.com/avatar.jpg');

    $task = domainApiTask(assignee: $user);

    $this->withToken($token)
        ->getJson("/api/v1/users/{$user->id}/tasks")
        ->assertOk()
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('data.0.assignee.id', $user->id);

    $this->withToken($token)
        ->deleteJson("/api/v1/users/{$user->id}")
        ->assertNoContent();

    $this->assertModelMissing($user);
});

test('user avatar uploads are stored on the configured avatar disk', function () {
    Storage::fake('r2');
    config(['filesystems.avatar_disk' => 'r2']);

    $user = User::factory()->create();
    $token = domainApiAccessToken();
    $avatar = UploadedFile::fake()->image('avatar.jpg');
    $expectedPath = 'avatars/'.$avatar->hashName();

    $this->withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => $avatar,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', "/storage/{$expectedPath}");

    Storage::disk('r2')->assertExists($expectedPath);
});

test('users can be listed with pagination filters search and sorting', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    User::factory()->projectManager()->create([
        'name' => 'Alpha PM',
        'email' => 'alpha.pm@example.com',
        'created_at' => now()->subDays(3),
    ]);
    User::factory()->projectManager()->create([
        'name' => 'Beta PM',
        'email' => 'beta.pm@example.com',
        'created_at' => now()->subDays(2),
    ]);
    User::factory()->create([
        'name' => 'Alpha Team Member',
        'email' => 'alpha.member@example.com',
        'created_at' => now()->subDay(),
    ]);

    $this->withToken($token)
        ->getJson('/api/v1/users?search=PM&role=projectManager&sortBy=name&order=desc&limit=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha PM')
        ->assertJsonPath('data.0.role', UserRole::ProjectManager->value)
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);
});

test('users list query parameters are validated', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    $this->withToken($token)
        ->getJson('/api/v1/users?limit=101&role=owner&sortBy=password&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'role', 'sortBy', 'order', 'page']);
});

test('tasks can be managed through the api', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = domainApiProject($creator);
    $token = domainApiAccessToken($creator);

    $taskId = $this->withToken($token)
        ->postJson('/api/v1/tasks', [
            'title' => 'Build dashboard',
            'description' => 'Project progress overview',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'project_id' => $project->id,
            'creator_id' => $creator->id,
            'assignee_id' => $assignee->id,
            'due_date' => '2026-08-10',
            'position' => 2,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Build dashboard')
        ->assertJsonPath('data.assigneeId', $assignee->id)
        ->json('data.id');

    $task = Task::query()->findOrFail($taskId);

    $this->withToken($token)
        ->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.project.id', $project->id)
        ->assertJsonPath('data.assignee.id', $assignee->id);

    $this->withToken($token)
        ->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', TaskStatus::InProgress->value);

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress);

    $this->withToken($token)
        ->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertModelMissing($task);
});

test('task requests validate enum values', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = domainApiProject($creator);
    $token = domainApiAccessToken($creator);

    $this->withToken($token)
        ->postJson('/api/v1/tasks', [
            'title' => 'Build dashboard',
            'status' => 'not-a-status',
            'project_id' => $project->id,
            'creator_id' => $creator->id,
            'assignee_id' => $assignee->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('comments can be managed through the api', function () {
    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);

    $commentId = $this->withToken($token)
        ->postJson('/api/v1/comments', [
            'content' => 'Looks ready for review.',
            'task_id' => $task->id,
            'author_id' => $author->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Looks ready for review.')
        ->json('data.id');

    $comment = Comment::query()->findOrFail($commentId);

    $this->withToken($token)
        ->patchJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Looks ready for QA.',
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Looks ready for QA.');

    $this->withToken($token)
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertModelMissing($comment);
});

test('attachments can be managed through the api', function () {
    $uploader = User::factory()->create();
    $task = domainApiTask(creator: $uploader);
    $token = domainApiAccessToken($uploader);

    $attachmentId = $this->withToken($token)
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

    $this->withToken($token)
        ->getJson("/api/v1/attachments/{$attachment->id}")
        ->assertOk()
        ->assertJsonPath('data.uploader.id', $uploader->id);

    $this->withToken($token)
        ->patchJson("/api/v1/attachments/{$attachment->id}", [
            'file_size' => 4096,
        ])
        ->assertOk()
        ->assertJsonPath('data.fileSize', 4096);

    $this->withToken($token)
        ->deleteJson("/api/v1/attachments/{$attachment->id}")
        ->assertNoContent();

    $this->assertModelMissing($attachment);
});
