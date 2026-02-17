<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Notifications\CommentAdded;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Comments
 *
 * APIs for managing task comments
 */
class CommentController extends Controller
{
    public function __construct(private CommentService $commentService)
    {
    }

    /**
     * List Comments
     *
     * Get all comments for a task.
     *
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Comment::class);

        $perPage = (int) ($request->per_page ?? 15);
        $comments = $this->commentService->listForTask($task->id, $perPage);

        return CommentResource::collection($comments);
    }

    /**
     * Create Comment
     *
     * @bodyParam body string required The comment body. Example: This looks great!
     */
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $this->commentService->create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        // Notify task creator and assignee
        $notifiables = collect([$task->creator, $task->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $user->id === $request->user()->id);

        foreach ($notifiables as $user) {
            $user->notify(new CommentAdded($comment, $task));
        }

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update Comment
     *
     * @bodyParam body string required The updated comment body.
     */
    public function update(UpdateCommentRequest $request, Task $task, Comment $comment): CommentResource
    {
        $this->authorize('update', $comment);

        $comment = $this->commentService->update($comment, $request->validated());

        return new CommentResource($comment->load('user'));
    }

    /**
     * Delete Comment
     */
    public function destroy(Task $task, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->delete($comment);

        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
