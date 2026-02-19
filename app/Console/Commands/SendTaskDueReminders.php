<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueReminder;
use Illuminate\Console\Command;

class SendTaskDueReminders extends Command
{
    protected $signature = 'tasks:send-due-reminders';
    protected $description = 'Send reminders for tasks due within 24 hours';

    public function handle(): int
    {
        $tasks = Task::withoutGlobalScopes()
            ->with(['assignee', 'project'])
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->whereDate('due_date', now()->addDay()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $task->assignee->notify(new TaskDueReminder($task));
            $count++;
        }

        $this->info("Sent {$count} due date reminder(s).");

        return Command::SUCCESS;
    }
}
