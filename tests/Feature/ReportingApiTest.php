<?php

use App\Models\ActivityLog;
use App\Models\Export;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

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
