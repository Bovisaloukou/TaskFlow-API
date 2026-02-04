<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskAttachmentController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Public invitation acceptance
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept']);

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Organization
        Route::get('organization', [OrganizationController::class, 'show']);
        Route::put('organization', [OrganizationController::class, 'update']);

        // Members
        Route::get('organization/members', [MemberController::class, 'index']);
        Route::delete('organization/members/{user}', [MemberController::class, 'destroy']);
        Route::put('organization/members/{user}/role', [MemberController::class, 'updateRole']);

        // Invitations
        Route::get('invitations', [InvitationController::class, 'index']);
        Route::post('invitations', [InvitationController::class, 'store']);
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy']);

        // Projects
        Route::apiResource('projects', ProjectController::class);

        // Tasks within projects
        Route::apiResource('projects.tasks', TaskController::class);

        // Cross-project tasks
        Route::get('tasks', [TaskController::class, 'all']);
        Route::get('tasks/my', [TaskController::class, 'my']);

        // Comments
        Route::apiResource('tasks.comments', CommentController::class)->except(['show']);

        // Attachments
        Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
        Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
        Route::get('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'show']);
        Route::delete('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);
    });
});
