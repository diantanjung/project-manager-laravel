<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Export;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
    Cache::flush();

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

test('dashboard exposes task progress deadlines priority and latest updates', function () {
    Cache::flush();
    Carbon::setTestNow('2026-07-28 09:00:00');

    try {
        $user = User::factory()->create();
        $project = domainApiProject($user);
        $token = domainApiAccessToken($user);

        $todoTask = domainApiTask($project, $user, $user);
        $todoTask->forceFill([
            'title' => 'Due soon todo',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_date' => '2026-07-31',
            'created_at' => '2026-07-28 09:01:00',
            'updated_at' => '2026-07-28 09:01:00',
        ])->saveQuietly();

        $doingTask = domainApiTask($project, $user, $user);
        $doingTask->forceFill([
            'title' => 'Urgent progress',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::Urgent,
            'due_date' => '2026-08-03',
            'created_at' => '2026-07-28 09:02:00',
            'updated_at' => '2026-07-28 09:05:00',
        ])->saveQuietly();

        $reviewTask = domainApiTask($project, $user, $user);
        $reviewTask->forceFill([
            'title' => 'Review copy',
            'status' => TaskStatus::Review,
            'priority' => TaskPriority::Low,
            'due_date' => '2026-08-10',
            'created_at' => '2026-07-28 09:03:00',
            'updated_at' => '2026-07-28 09:03:00',
        ])->saveQuietly();

        $overdueTask = domainApiTask($project, $user, $user);
        $overdueTask->forceFill([
            'title' => 'Overdue high',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::High,
            'due_date' => '2026-07-27',
            'created_at' => '2026-07-28 09:04:00',
            'updated_at' => '2026-07-28 09:04:00',
        ])->saveQuietly();

        $doneOverdueTask = domainApiTask($project, $user, $user);
        $doneOverdueTask->forceFill([
            'title' => 'Done overdue',
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::Urgent,
            'due_date' => '2026-07-20',
            'created_at' => '2026-07-28 09:05:00',
            'updated_at' => '2026-07-28 09:06:00',
        ])->saveQuietly();

        ActivityLog::query()->create([
            'actor_id' => $user->id,
            'entity_type' => Task::class,
            'entity_id' => $doingTask->id,
            'action' => 'task.status_updated',
            'after' => ['status' => TaskStatus::InProgress->value],
            'created_at' => '2026-07-28 09:07:00',
        ]);

        withToken($token);

        getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('activeProgress.doing', 1)
            ->assertJsonPath('activeProgress.todo', 2)
            ->assertJsonPath('activeProgress.total', 3)
            ->assertJsonPath('activeProgress.ratio', 0.3333)
            ->assertJsonPath('activeProgress.percentage', 33.33)
            ->assertJsonPath('inReview', 1)
            ->assertJsonPath('dueSoon', 2)
            ->assertJsonPath('overdue', 1)
            ->assertJsonPath('overdueTaskCount', 1)
            ->assertJsonPath('recentTasks.0.id', $doneOverdueTask->id)
            ->assertJsonPath('recentTasks.0.project.id', $project->id)
            ->assertJsonPath('recentTasks.0.project.name', $project->name)
            ->assertJsonMissingPath('recentTasks.0.creator')
            ->assertJsonMissingPath('recentTasks.0.assignee')
            ->assertJsonMissingPath('recentTasks.0.comments')
            ->assertJsonMissingPath('recentTasks.0.attachments')
            ->assertJsonMissingPath('recentTasks.0.checklistItems')
            ->assertJsonMissingPath('recentTasks.0.assignedUsers')
            ->assertJsonPath('upcomingDeadlines.0.id', $todoTask->id)
            ->assertJsonPath('upcomingDeadlines.1.id', $doingTask->id)
            ->assertJsonPath('highPriorityTasks.0.id', $doingTask->id)
            ->assertJsonPath('highPriorityTasks.1.id', $overdueTask->id)
            ->assertJsonPath('latestUpdates.0.action', 'task.status_updated');
    } finally {
        Carbon::setTestNow();
    }
});

test('dashboard response is cached per user for fifteen seconds', function () {
    Cache::flush();

    $user = User::factory()->create();
    $project = domainApiProject($user);
    $task = domainApiTask($project, $user, $user);
    $token = domainApiAccessToken($user);

    withToken($token);

    getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('taskCountPerStatus.todo', 1)
        ->assertJsonPath('taskCountPerStatus.done', 0);

    $task->forceFill([
        'status' => TaskStatus::Done,
    ])->saveQuietly();

    withToken($token);

    getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('taskCountPerStatus.todo', 1)
        ->assertJsonPath('taskCountPerStatus.done', 0);

    Cache::forget("dashboard:user:{$user->id}");

    withToken($token);

    getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('taskCountPerStatus.todo', 0)
        ->assertJsonPath('taskCountPerStatus.done', 1);
});
