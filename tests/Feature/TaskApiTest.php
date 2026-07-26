<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\withToken;

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

    withToken($token)
        ->getJson("/api/v1/tasks/{$task->id}")
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
