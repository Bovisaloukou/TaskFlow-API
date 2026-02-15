<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Tasks
 *
 * APIs for managing tasks
 */
class TaskController extends Controller
{
    public function __construct(private TaskService $taskService)
    {
    }

    /**
     * List Tasks (within project)
     *
     * @queryParam status string Filter by status. Example: todo
     * @queryParam priority string Filter by priority. Example: high
     * @queryParam assigned_to integer Filter by assignee user ID. Example: 1
     * @queryParam due_before string Filter tasks due before date. Example: 2026-12-31
     * @queryParam due_after string Filter tasks due after date. Example: 2026-01-01
     * @queryParam search string Search title and description. Example: homepage
     * @queryParam sort_by string Sort column. Example: priority
     * @queryParam sort_dir string Sort direction. Example: desc
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);
        $this->authorize('view', $project);

        $tasks = $this->taskService->list($request, $project->id);

        return TaskResource::collection($tasks);
    }

    /**
     * Create Task
     *
     * @bodyParam title string required The task title. Example: Design homepage
     * @bodyParam description string The task description.
     * @bodyParam status string The task status. Example: todo
     * @bodyParam priority string The task priority. Example: high
     * @bodyParam assigned_to integer The assignee user ID. Example: 1
     * @bodyParam parent_task_id integer Parent task ID for subtasks.
     * @bodyParam due_date string Due date (Y-m-d). Example: 2026-03-15
     * @bodyParam position integer Sort position. Example: 0
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $task = $this->taskService->create([
            ...$request->validated(),
            'project_id' => $project->id,
            'created_by' => $request->user()->id,
        ]);

        if ($task->assigned_to && $task->assigned_to !== $request->user()->id) {
            $task->assignee->notify(new TaskAssigned($task));
        }

        return (new TaskResource($task->load(['project', 'assignee', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show Task
     */
    public function show(Project $project, Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource(
            $task->load(['project', 'assignee', 'creator', 'subtasks'])
                ->loadCount(['comments', 'attachments'])
        );
    }

    /**
     * Update Task
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $oldAssignee = $task->assigned_to;

        $task = $this->taskService->update($task, $request->validated());

        if ($task->assigned_to && $task->assigned_to !== $oldAssignee && $task->assigned_to !== $request->user()->id) {
            $task->assignee->notify(new TaskAssigned($task));
        }

        return new TaskResource($task->load(['project', 'assignee', 'creator']));
    }

    /**
     * Delete Task
     */
    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    /**
     * All Tasks (cross-project)
     *
     * Get all tasks across all projects in the organization.
     *
     * @queryParam status string Filter by status.
     * @queryParam priority string Filter by priority.
     * @queryParam assigned_to integer Filter by assignee.
     * @queryParam due_before string Filter by due date.
     * @queryParam due_after string Filter by due date.
     * @queryParam search string Search title/description.
     * @queryParam sort_by string Sort column.
     * @queryParam sort_dir string Sort direction.
     * @queryParam per_page integer Items per page.
     */
    public function all(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->taskService->list($request);

        return TaskResource::collection($tasks);
    }

    /**
     * My Tasks
     *
     * Get all tasks assigned to the authenticated user.
     */
    public function my(Request $request): AnonymousResourceCollection
    {
        $tasks = $this->taskService->myTasks($request);

        return TaskResource::collection($tasks);
    }
}
