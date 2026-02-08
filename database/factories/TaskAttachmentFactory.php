<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'task_id' => Task::factory()->state(['organization_id' => $organization]),
            'uploaded_by' => User::factory()->for($organization),
            'file_name' => fake()->word() . '.pdf',
            'file_path' => 'attachments/' . fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => 'application/pdf',
        ];
    }
}
