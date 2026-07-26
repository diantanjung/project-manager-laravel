<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('project tasks can be listed with pagination filters search and sorting', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);
    $project = domainApiProject($owner);
    $firstTask = domainApiTask($project, $owner);
    $secondTask = domainApiTask($project, $owner);

    $firstTask->update([
        'title' => 'Alpha Wireframes',
        'status' => TaskStatus::Todo,
        'position' => 2,
    ]);
    $secondTask->update([
        'title' => 'Beta Wireframes',
        'status' => TaskStatus::Todo,
        'position' => 1,
    ]);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}/tasks?search=wireframes&status=todo&sortBy=position&order=asc&limit=1&page=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $secondTask->id)
        ->assertJsonPath('data.0.creator.id', $owner->id)
        ->assertJsonPath('pagination.page', 1)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);
});

test('tasks can be listed with pagination filters search and sorting', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $otherAssignee = User::factory()->create();
    $project = domainApiProject($creator);
    $otherProject = domainApiProject($creator);
    $token = domainApiAccessToken($creator);

    $firstTask = domainApiTask($project, $creator, $assignee);
    $firstTask->update([
        'title' => 'Alpha Dashboard',
        'description' => 'Dashboard reporting',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'due_date' => '2026-08-01',
    ]);

    $secondTask = domainApiTask($project, $creator, $assignee);
    $secondTask->update([
        'title' => 'Beta Dashboard',
        'description' => 'Dashboard analytics',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'due_date' => '2026-08-10',
    ]);

    $otherTask = domainApiTask($otherProject, $creator, $otherAssignee);
    $otherTask->update([
        'title' => 'Gamma Dashboard',
        'description' => null,
        'status' => TaskStatus::Done,
        'priority' => TaskPriority::Low,
        'due_date' => '2026-08-15',
    ]);

    withToken($token);

    getJson("/api/v1/tasks?search=dashboard&status=todo&priority=high&projectId={$project->id}&creatorId={$creator->id}&assigneeId={$assignee->id}&dueFrom=2026-08-01&dueUntil=2026-08-31&hasDescription=true&sortBy=due_date&order=desc&limit=1&page=2")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $firstTask->id)
        ->assertJsonPath('data.0.project.id', $project->id)
        ->assertJsonPath('data.0.project.owner.id', $creator->id)
        ->assertJsonPath('data.0.creator.id', $creator->id)
        ->assertJsonPath('data.0.assignee.id', $assignee->id)
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);

    withToken($token);

    getJson('/api/v1/tasks?hasDescription=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $otherTask->id);
});

test('tasks list query parameters are validated', function () {
    $token = domainApiAccessToken();

    withToken($token);

    getJson('/api/v1/tasks?limit=101&status=not-a-status&priority=critical&projectId=999999&creatorId=999999&assigneeId=999999&dueFrom=2026-08-10&dueUntil=2026-08-01&hasDescription=maybe&sortBy=project_id&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'limit',
            'status',
            'priority',
            'projectId',
            'creatorId',
            'assigneeId',
            'dueUntil',
            'hasDescription',
            'sortBy',
            'order',
            'page',
        ]);
});
