<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'task_id' => Task::factory()->state(['organization_id' => $organization]),
            'user_id' => User::factory()->for($organization),
            'body' => fake()->paragraph(),
        ];
    }
}
