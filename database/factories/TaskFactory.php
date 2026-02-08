<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'project_id' => Project::factory()->state(['organization_id' => $organization]),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in_progress', 'in_review', 'done', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'assigned_to' => null,
            'created_by' => User::factory()->for($organization),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function todo(): static
    {
        return $this->state(fn () => ['status' => 'todo']);
    }

    public function withAssignee(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }
}
