<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('assignee is notified by mail when a task is created', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::query()->create([
        'name' => 'Website redesign',
        'owner_id' => $creator->id,
    ]);

    $task = Task::query()->create([
        'title' => 'Create wireframes',
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $assignee->id,
    ]);

    Notification::assertSentTo(
        $assignee,
        fn (TaskAssigned $notification, array $channels): bool => $notification->task->is($task)
            && $channels === ['mail'],
    );
});

test('task assigned notification uses resend mailer', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::query()->create([
        'name' => 'Website redesign',
        'owner_id' => $creator->id,
    ]);
    $task = Task::withoutEvents(fn (): Task => Task::query()->create([
        'title' => 'Create wireframes',
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $assignee->id,
    ]));

    $notification = new TaskAssigned($task);
    $mailMessage = $notification->toMail($assignee);

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($mailMessage->mailer)->toBe('resend')
        ->and($mailMessage->subject)->toBe('Task assigned: Create wireframes');
});

test('new assignee is notified when task assignment changes', function () {
    $creator = User::factory()->create();
    $oldAssignee = User::factory()->create();
    $newAssignee = User::factory()->create();
    $project = Project::query()->create([
        'name' => 'Website redesign',
        'owner_id' => $creator->id,
    ]);

    $task = Task::withoutEvents(fn (): Task => Task::query()->create([
        'title' => 'Create wireframes',
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $oldAssignee->id,
    ]));

    Notification::fake();

    $task->update([
        'assignee_id' => $newAssignee->id,
    ]);

    Notification::assertSentTo(
        $newAssignee,
        fn (TaskAssigned $notification, array $channels): bool => $notification->task->is($task)
            && $channels === ['mail'],
    );
    Notification::assertNotSentTo($oldAssignee, TaskAssigned::class);
});

test('assignee is not notified when another task field changes', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::query()->create([
        'name' => 'Website redesign',
        'owner_id' => $creator->id,
    ]);

    $task = Task::withoutEvents(fn (): Task => Task::query()->create([
        'title' => 'Create wireframes',
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $assignee->id,
    ]));

    Notification::fake();

    $task->update([
        'title' => 'Create high-fidelity wireframes',
    ]);

    Notification::assertNothingSent();
});
