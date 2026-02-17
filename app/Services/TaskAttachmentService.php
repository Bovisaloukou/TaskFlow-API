<?php

namespace App\Services;

use App\Models\TaskAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentService
{
    public function upload(UploadedFile $file, int $taskId, int $organizationId, int $uploadedBy): TaskAttachment
    {
        $path = $file->store("attachments/{$organizationId}/{$taskId}", 'local');

        return TaskAttachment::create([
            'organization_id' => $organizationId,
            'task_id' => $taskId,
            'uploaded_by' => $uploadedBy,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    public function delete(TaskAttachment $attachment): void
    {
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();
    }

    public function getDownloadPath(TaskAttachment $attachment): string
    {
        return Storage::disk('local')->path($attachment->file_path);
    }
}
