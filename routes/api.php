<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChecklistItemController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WebhookDeliveryController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('health', HealthController::class)->name('health');

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:login')
            ->name('register');

        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        Route::post('refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:login')
            ->name('refresh');

        Route::middleware('api.auth')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('signed')
        ->name('attachments.download');

    Route::middleware('api.auth')->group(function (): void {
        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');

        Route::post('users/{user}/avatar', [UserController::class, 'avatar'])
            ->name('users.avatar');
        Route::get('users/{user}/tasks', [UserController::class, 'tasks'])
            ->name('users.tasks');
        Route::get('teams/{team}/members', [TeamController::class, 'members'])
            ->name('teams.members.index');
        Route::post('teams/{team}/members', [TeamController::class, 'addMember'])
            ->name('teams.members.store');
        Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])
            ->name('teams.members.destroy');

        Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks'])
            ->name('projects.tasks');
        Route::get('projects/{project}/activity', [ProjectController::class, 'activity'])
            ->name('projects.activity');
        Route::get('projects/{project}/summary', [ProjectController::class, 'summary'])
            ->name('projects.summary');
        Route::post('projects/{project}/teams', [ProjectController::class, 'assignTeam'])
            ->name('projects.teams.store');
        Route::delete('projects/{project}/teams/{team}', [ProjectController::class, 'removeTeam'])
            ->name('projects.teams.destroy');

        Route::post('tasks/reorder', [TaskController::class, 'reorder'])
            ->name('tasks.reorder');
        Route::patch('tasks/{task}/status', [TaskController::class, 'status'])
            ->name('tasks.status');
        Route::get('tasks/{task}/comments', [TaskController::class, 'comments'])
            ->name('tasks.comments.index');
        Route::post('tasks/{task}/comments', [CommentController::class, 'store'])
            ->name('tasks.comments.store');
        Route::get('tasks/{task}/attachments', [TaskController::class, 'attachments'])
            ->name('tasks.attachments.index');
        Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store'])
            ->name('tasks.attachments.store');
        Route::get('tasks/{task}/activity', [TaskController::class, 'activity'])
            ->name('tasks.activity');
        Route::post('tasks/{task}/assignments', [TaskController::class, 'assignUser'])
            ->name('tasks.assignments.store');
        Route::delete('tasks/{task}/assignments/{user}', [TaskController::class, 'removeUserAssignment'])
            ->name('tasks.assignments.destroy');
        Route::post('tasks/{task}/checklist-items', [ChecklistItemController::class, 'store'])
            ->name('tasks.checklist-items.store');

        Route::patch('checklist-items/{item}', [ChecklistItemController::class, 'update'])
            ->name('checklist-items.update');
        Route::delete('checklist-items/{item}', [ChecklistItemController::class, 'destroy'])
            ->name('checklist-items.destroy');

        Route::get('dashboard', DashboardController::class)
            ->name('dashboard');
        Route::post('exports/project-report', [ExportController::class, 'projectReport'])
            ->name('exports.project-report');
        Route::get('exports/{export}', [ExportController::class, 'show'])
            ->name('exports.show');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
            ->name('activity-logs.index');
        Route::apiResource('webhook-endpoints', WebhookEndpointController::class)
            ->parameters(['webhook-endpoints' => 'webhookEndpoint'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('webhook-deliveries', [WebhookDeliveryController::class, 'index'])
            ->name('webhook-deliveries.index');

        Route::apiResources([
            'projects' => ProjectController::class,
            'tasks' => TaskController::class,
            'teams' => TeamController::class,
            'users' => UserController::class,
            'comments' => CommentController::class,
            'attachments' => AttachmentController::class,
        ]);
    });
});
