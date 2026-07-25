<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\ReorderTasksRequest;
use App\Http\Requests\Api\V1\Tasks\StoreTaskAssignmentRequest;
use App\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskStatusRequest;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Http\Resources\Api\V1\AttachmentResource;
use App\Http\Resources\Api\V1\CommentResource;
use App\Http\Resources\Api\V1\TaskResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TaskResource::collection(
            Task::query()
                ->with(['project', 'creator', 'assignee'])
                ->latest()
                ->get()
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::query()->create($request->validated());

        return (new TaskResource($task->load(['project', 'creator', 'assignee'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource(
            $task->load(['project', 'creator', 'assignee', 'comments.author', 'attachments.uploader', 'assignedUsers'])
        );
    }

    public function status(UpdateTaskStatusRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }

    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('tasks') as $taskOrder) {
                Task::query()
                    ->whereKey($taskOrder['id'])
                    ->update(['position' => $taskOrder['position']]);
            }
        });

        return response()->json([
            'message' => 'Tasks reordered successfully.',
        ]);
    }

    public function comments(Task $task): AnonymousResourceCollection
    {
        return CommentResource::collection(
            $task->comments()
                ->with('author')
                ->latest()
                ->get()
        );
    }

    public function attachments(Task $task): AnonymousResourceCollection
    {
        return AttachmentResource::collection(
            $task->attachments()
                ->with('uploader')
                ->latest()
                ->get()
        );
    }

    public function activity(Task $task): AnonymousResourceCollection
    {
        return ActivityLogResource::collection(
            ActivityLog::query()
                ->with('actor')
                ->where('entity_type', Task::class)
                ->where('entity_id', $task->id)
                ->latest('created_at')
                ->get()
        );
    }

    public function assignUser(StoreTaskAssignmentRequest $request, Task $task): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->findOrFail($validated['user_id']);

        if ($task->assignedUsers()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'User is already assigned to this task.',
            ], Response::HTTP_CONFLICT);
        }

        $task->assignedUsers()->attach($user, [
            'assigned_by' => $validated['assigned_by'] ?? $request->user()->id,
        ]);

        $assignedUser = $task->assignedUsers()->whereKey($user->id)->firstOrFail();

        return (new UserResource($assignedUser))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function removeUserAssignment(Task $task, User $user): Response
    {
        if (! $task->assignedUsers()->whereKey($user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND, 'User is not assigned to this task.');
        }

        $task->assignedUsers()->detach($user);

        return response()->noContent();
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }

    public function destroy(Task $task): Response
    {
        $task->delete();

        return response()->noContent();
    }
}
