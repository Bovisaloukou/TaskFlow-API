<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\TaskAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group Attachments
 *
 * APIs for managing task attachments
 */
class TaskAttachmentController extends Controller
{
    public function __construct(private TaskAttachmentService $attachmentService)
    {
    }

    /**
     * List Attachments
     *
     * Get all attachments for a task.
     */
    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TaskAttachment::class);

        $attachments = TaskAttachment::with('uploader')
            ->where('task_id', $task->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return TaskAttachmentResource::collection($attachments);
    }

    /**
     * Upload Attachment
     *
     * @bodyParam file file required The file to upload (max 10MB).
     */
    public function store(StoreAttachmentRequest $request, Task $task): JsonResponse
    {
        $attachment = $this->attachmentService->upload(
            $request->file('file'),
            $task->id,
            $request->user()->organization_id,
            $request->user()->id
        );

        return (new TaskAttachmentResource($attachment->load('uploader')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download Attachment
     */
    public function show(Task $task, TaskAttachment $attachment): BinaryFileResponse
    {
        $this->authorize('view', $attachment);

        $path = $this->attachmentService->getDownloadPath($attachment);

        return response()->download($path, $attachment->file_name);
    }

    /**
     * Delete Attachment
     */
    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        $this->attachmentService->delete($attachment);

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }
}
