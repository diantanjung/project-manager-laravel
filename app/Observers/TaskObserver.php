<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TaskObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->notifyAssignee($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        if (! $task->wasChanged('assignee_id')) {
            return;
        }

        $this->notifyAssignee($task);
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }

    protected function notifyAssignee(Task $task): void
    {
        $assignee = User::query()->find($task->assignee_id);

        $assignee?->notify(new TaskAssigned($task));
    }
}
