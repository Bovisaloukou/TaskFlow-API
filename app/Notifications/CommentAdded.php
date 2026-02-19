<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentAdded extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment, public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment on: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->comment->user->name . ' commented on **' . $this->task->title . '**:')
            ->line('"' . \Illuminate\Support\Str::limit($this->comment->body, 200) . '"');
    }
}
