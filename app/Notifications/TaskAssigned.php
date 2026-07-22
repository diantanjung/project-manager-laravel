<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
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
        $notifiableName = $notifiable instanceof User ? $notifiable->name : 'there';

        return (new MailMessage)
            ->mailer('resend')
            ->subject("Task assigned: {$task->title}")
            ->greeting("Hi {$notifiableName},")
            ->line("{$task->creator->name} assigned you a task in {$task->project->name}.")
            ->line("Task: {$task->title}")
            ->action('Open task', url("/tasks/{$task->id}"))
            ->line('Please review it when you have a moment.');
    }
}
