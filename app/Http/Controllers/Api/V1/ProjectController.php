<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Projects
 *
 * APIs for managing projects
 */
class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService)
    {
    }

    /**
     * List Projects
     *
     * Get a paginated list of projects.
     *
     * @queryParam status string Filter by status (active, archived, completed). Example: active
     * @queryParam search string Search by name or description. Example: website
     * @queryParam sort_by string Sort by column (name, status, created_at, updated_at). Example: created_at
     * @queryParam sort_dir string Sort direction (asc, desc). Example: desc
     * @queryParam per_page integer Items per page (max 100). Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->projectService->list($request);

        return ProjectResource::collection($projects);
    }

    /**
     * Create Project
     *
     * @bodyParam name string required The project name. Example: Website Redesign
     * @bodyParam description string The project description. Example: Redesign the company website
     * @bodyParam status string The project status. Example: active
     * @bodyParam color string Hex color code. Example: #3498db
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return (new ProjectResource($project->load('creator')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show Project
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load('creator')->loadCount('tasks'));
    }

    /**
     * Update Project
     *
     * @bodyParam name string The project name. Example: Website Redesign v2
     * @bodyParam description string The project description.
     * @bodyParam status string The project status. Example: completed
     * @bodyParam color string Hex color code. Example: #e74c3c
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update($project, $request->validated());

        return new ProjectResource($project->load('creator'));
    }

    /**
     * Delete Project
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return response()->json(['message' => 'Project deleted successfully.']);
    }
}
