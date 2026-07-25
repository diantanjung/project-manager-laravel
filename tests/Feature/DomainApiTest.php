<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Export;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Auth\AuthTokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

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
    getJson('/api/v1/projects')->assertUnauthorized();
});

test('projects can be managed through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);

    $createdProjectId = withToken($token)
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
    assertModelExists($project);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.owner.id', $owner->id);

    withToken($token)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Mobile app launch',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Mobile app launch');

    expect($project->refresh()->name)->toBe('Mobile app launch');

    withToken($token)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertNoContent();

    assertModelMissing($project);
});

test('teams can be managed through the api', function () {
    $token = domainApiAccessToken();

    $teamId = withToken($token)
        ->postJson('/api/v1/teams', [
            'name' => 'Engineering',
            'description' => 'Product engineering',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Engineering')
        ->json('data.id');

    $team = Team::query()->findOrFail($teamId);

    withToken($token);

    getJson('/api/v1/teams')
        ->assertOk()
        ->assertJsonPath('data.0.id', $team->id);

    withToken($token)
        ->patchJson("/api/v1/teams/{$team->id}", [
            'description' => 'Platform engineering',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Platform engineering');

    withToken($token)
        ->deleteJson("/api/v1/teams/{$team->id}")
        ->assertNoContent();

    assertModelMissing($team);
});

test('teams can be listed with pagination filters search and sorting', function () {
    $token = domainApiAccessToken();

    Team::query()->create([
        'name' => 'Alpha Engineering',
        'description' => 'Platform delivery',
        'created_at' => now()->subDays(3),
    ]);
    Team::query()->create([
        'name' => 'Beta Engineering',
        'description' => 'Product delivery',
        'created_at' => now()->subDays(2),
    ]);
    Team::query()->create([
        'name' => 'Gamma Operations',
        'description' => null,
        'created_at' => now()->subDay(),
    ]);

    withToken($token);

    getJson('/api/v1/teams?search=engineering&hasDescription=true&sortBy=name&order=desc&limit=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Engineering')
        ->assertJsonPath('data.0.description', 'Platform delivery')
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);

    withToken($token);

    getJson('/api/v1/teams?hasDescription=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gamma Operations');
});

test('teams list query parameters are validated', function () {
    $token = domainApiAccessToken();

    withToken($token);

    getJson('/api/v1/teams?limit=101&hasDescription=maybe&sortBy=description&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'hasDescription', 'sortBy', 'order', 'page']);
});

test('members can be added to teams through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => $member->id,
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $team->id)
        ->assertJsonPath('data.members.0.id', $member->id);

    $teamMember = $team->members()->whereKey($member->id)->firstOrFail();

    expect($teamMember->pivot->role)->toBe('admin')
        ->and($teamMember->pivot->joined_at)->not->toBeNull();
});

test('team members can be listed through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($admin, [
        'role' => 'admin',
        'joined_at' => now()->subDay(),
    ]);
    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token);

    getJson("/api/v1/teams/{$team->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $member->id)
        ->assertJsonPath('data.0.membership.role', 'member')
        ->assertJsonPath('data.1.id', $admin->id)
        ->assertJsonPath('data.1.membership.role', 'admin');
});

test('duplicate team membership returns a conflict', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => $member->id,
        ])
        ->assertConflict()
        ->assertJsonPath('message', 'User is already a member of this team.');
});

test('team member payload is validated', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => 999999,
            'role' => 'maintainer',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'role']);
});

test('members can be removed from teams through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token)
        ->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")
        ->assertNoContent();

    expect($team->members()->whereKey($member->id)->exists())->toBeFalse();
});

test('teams can be assigned to projects through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);
    $project = domainApiProject($owner);
    $team = Team::query()->create([
        'name' => 'Platform',
        'description' => 'Platform team',
    ]);

    withToken($token)
        ->postJson("/api/v1/projects/{$project->id}/teams", [
            'team_id' => $team->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.assignedTeams.0.id', $team->id);

    expect($project->assignedTeams()->whereKey($team->id)->exists())->toBeTrue();

    withToken($token)
        ->postJson("/api/v1/projects/{$project->id}/teams", [
            'team_id' => $team->id,
        ])
        ->assertConflict()
        ->assertJsonPath('message', 'Team is already assigned to this project.');

    withToken($token)
        ->deleteJson("/api/v1/projects/{$project->id}/teams/{$team->id}")
        ->assertNoContent();

    expect($project->assignedTeams()->whereKey($team->id)->exists())->toBeFalse();
});

test('project tasks can be listed through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);
    $project = domainApiProject($owner);
    $firstTask = domainApiTask($project, $owner);
    $secondTask = domainApiTask($project, $owner);

    $firstTask->update(['position' => 2]);
    $secondTask->update(['position' => 1]);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}/tasks")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $secondTask->id)
        ->assertJsonPath('data.1.id', $firstTask->id);
});

test('users can be managed through the api', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    $userId = withToken($token)
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
    assertModelExists($user);

    withToken($token);

    getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $user->id,
            'email' => 'dian.pm@example.com',
        ]);

    withToken($token);

    getJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'dian.pm@example.com');

    withToken($token)
        ->patchJson("/api/v1/users/{$user->id}", [
            'name' => 'Dian Delivery Lead',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Dian Delivery Lead')
        ->assertJsonPath('data.isActive', false);

    expect($user->refresh()->name)->toBe('Dian Delivery Lead')
        ->and($user->is_active)->toBeFalse();

    withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar_url' => 'https://example.com/avatar.jpg',
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', 'https://example.com/avatar.jpg');

    $task = domainApiTask(assignee: $user);

    withToken($token);

    getJson("/api/v1/users/{$user->id}/tasks")
        ->assertOk()
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('data.0.assignee.id', $user->id);

    withToken($token)
        ->deleteJson("/api/v1/users/{$user->id}")
        ->assertNoContent();

    assertModelMissing($user);
});

test('user avatar uploads are stored on the configured avatar disk', function () {
    Storage::fake('r2');
    config(['filesystems.avatar_disk' => 'r2']);

    $user = User::factory()->create();
    $token = domainApiAccessToken();
    $avatar = UploadedFile::fake()->image('avatar.jpg');
    $expectedPath = 'avatars/'.$avatar->hashName();

    withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => $avatar,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', "/storage/{$expectedPath}");

    expect(Storage::disk('r2')->exists($expectedPath))->toBeTrue();
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

    withToken($token);

    getJson('/api/v1/users?search=PM&role=projectManager&sortBy=name&order=desc&limit=1&page=2')
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

    withToken($token);

    getJson('/api/v1/users?limit=101&role=owner&sortBy=password&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'role', 'sortBy', 'order', 'page']);
});

test('tasks can be managed through the api', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = domainApiProject($creator);
    $token = domainApiAccessToken($creator);

    $taskId = withToken($token)
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

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.project.id', $project->id)
        ->assertJsonPath('data.assignee.id', $assignee->id);

    withToken($token)
        ->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', TaskStatus::InProgress->value);

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress);

    withToken($token)
        ->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertNoContent();

    assertModelMissing($task);
});

test('task requests validate enum values', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = domainApiProject($creator);
    $token = domainApiAccessToken($creator);

    withToken($token)
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

test('task status and order can be updated through dedicated endpoints', function () {
    $creator = User::factory()->create();
    $project = domainApiProject($creator);
    $token = domainApiAccessToken($creator);
    $firstTask = domainApiTask($project, $creator);
    $secondTask = domainApiTask($project, $creator);

    withToken($token)
        ->patchJson("/api/v1/tasks/{$firstTask->id}/status", [
            'status' => TaskStatus::Review->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', TaskStatus::Review->value);

    expect($firstTask->refresh()->status)->toBe(TaskStatus::Review);

    withToken($token)
        ->postJson('/api/v1/tasks/reorder', [
            'tasks' => [
                ['id' => $firstTask->id, 'position' => 10],
                ['id' => $secondTask->id, 'position' => 20],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Tasks reordered successfully.');

    expect($firstTask->refresh()->position)->toBe(10)
        ->and($secondTask->refresh()->position)->toBe(20);
});

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

test('checklist items can be managed through the api', function () {
    $user = User::factory()->create();
    $task = domainApiTask(creator: $user);
    $token = domainApiAccessToken($user);

    $itemId = withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/checklist-items", [
            'title' => 'Confirm acceptance criteria',
            'position' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.title', 'Confirm acceptance criteria')
        ->json('data.id');

    $item = ChecklistItem::query()->findOrFail($itemId);

    withToken($token)
        ->patchJson("/api/v1/checklist-items/{$item->id}", [
            'is_checked' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.isChecked', true);

    expect($item->refresh()->is_checked)->toBeTrue();

    withToken($token)
        ->deleteJson("/api/v1/checklist-items/{$item->id}")
        ->assertNoContent();

    assertModelMissing($item);
});

test('activity and summary endpoints expose project and task state', function () {
    $actor = User::factory()->create();
    $project = domainApiProject($actor);
    $task = domainApiTask($project, $actor);
    $token = domainApiAccessToken($actor);

    ActivityLog::query()->create([
        'actor_id' => $actor->id,
        'entity_type' => Project::class,
        'entity_id' => $project->id,
        'action' => 'project.updated',
        'after' => ['name' => $project->name],
    ]);
    ActivityLog::query()->create([
        'actor_id' => $actor->id,
        'entity_type' => Task::class,
        'entity_id' => $task->id,
        'action' => 'task.updated',
        'after' => ['title' => $task->title],
    ]);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}/summary")
        ->assertOk()
        ->assertJsonPath('projectId', $project->id)
        ->assertJsonPath('totalTasks', 1);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}/activity")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    withToken($token);

    getJson("/api/v1/tasks/{$task->id}/activity")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.action', 'task.updated');

    withToken($token);

    getJson('/api/v1/activity-logs')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

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

test('dashboard and project report exports are available through the api', function () {
    $user = User::factory()->create();
    $project = domainApiProject($user);
    domainApiTask($project, $user);
    $token = domainApiAccessToken($user);

    withToken($token);

    getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('totalActiveProjects', 1)
        ->assertJsonPath('taskCountPerStatus.todo', 1);

    $exportId = withToken($token)
        ->postJson('/api/v1/exports/project-report', [
            'project_id' => $project->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'project-report')
        ->json('data.id');

    $export = Export::query()->findOrFail($exportId);

    withToken($token);

    getJson("/api/v1/exports/{$export->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $export->id)
        ->assertJsonPath('data.status', 'completed');
});

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

test('comments can be managed through the api', function () {
    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);

    $commentId = withToken($token)
        ->postJson('/api/v1/comments', [
            'content' => 'Looks ready for review.',
            'task_id' => $task->id,
            'author_id' => $author->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Looks ready for review.')
        ->json('data.id');

    $comment = Comment::query()->findOrFail($commentId);

    withToken($token)
        ->patchJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Looks ready for QA.',
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Looks ready for QA.');

    withToken($token)
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertNoContent();

    assertModelMissing($comment);
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
