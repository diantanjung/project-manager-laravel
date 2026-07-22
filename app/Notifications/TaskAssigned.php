<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task->loadMissing(['creator', 'project']);
        $projectName = $task->project?->name ?? 'the project';
        $creatorName = $task->creator?->name ?? 'Someone';

        return (new MailMessage)
            ->mailer('resend')
            ->subject("Task assigned: {$task->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$creatorName} assigned you a task in {$projectName}.")
            ->line("Task: {$task->title}")
            ->action('Open task', url("/tasks/{$task->id}"))
            ->line('Please review it when you have a moment.');
    }
}
