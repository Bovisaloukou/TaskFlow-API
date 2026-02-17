<?php

namespace App\Services;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentService
{
    public function listForTask(int $taskId, int $perPage = 15): LengthAwarePaginator
    {
        return Comment::with('user')
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->paginate(min($perPage, 100));
    }

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);
        return $comment->fresh();
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
