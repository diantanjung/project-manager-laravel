<?php

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamController;
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

    Route::middleware('api.auth')->group(function (): void {
        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');

        Route::apiResources([
            'projects' => ProjectController::class,
            'tasks' => TaskController::class,
            'teams' => TeamController::class,
            'comments' => CommentController::class,
            'attachments' => AttachmentController::class,
        ]);
    });
});
