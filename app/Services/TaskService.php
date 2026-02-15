<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TaskService
{
    public function list(Request $request, ?int $projectId = null): LengthAwarePaginator
    {
        $query = Task::with(['project', 'assignee', 'creator']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $this->applyFilters($query, $request);

        $sortBy = in_array($request->sort_by, ['title', 'status', 'priority', 'due_date', 'position', 'created_at', 'updated_at'])
            ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($request->per_page ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function myTasks(Request $request): LengthAwarePaginator
    {
        $query = Task::with(['project', 'assignee', 'creator'])
            ->where('assigned_to', $request->user()->id);

        $this->applyFilters($query, $request);

        $sortBy = in_array($request->sort_by, ['title', 'status', 'priority', 'due_date', 'position', 'created_at', 'updated_at'])
            ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($request->per_page ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        if (isset($data['status']) && $data['status'] === 'done' && $task->status !== 'done') {
            $data['completed_at'] = now();
        }

        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    protected function applyFilters($query, Request $request): void
    {
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority));
        $query->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->assigned_to));
        $query->when($request->filled('due_before'), fn ($q) => $q->where('due_date', '<=', $request->due_before));
        $query->when($request->filled('due_after'), fn ($q) => $q->where('due_date', '>=', $request->due_after));
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        });
    }
}
