<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\IndexTaskRequest;
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
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Task::class);

        $validated = $request->validated();
        $user = $request->user();
        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sortBy = $validated['sortBy'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        /** @var LengthAwarePaginator<int, Task> $tasks */
        $tasks = Task::query()
            ->with(['project.owner', 'creator', 'assignee'])
            ->when(! $user->isAdmin(), function ($query) use ($user): void {
                $query->where(function ($query) use ($user): void {
                    $query->where('creator_id', $user->id)
                        ->orWhere('assignee_id', $user->id)
                        ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id))
                        ->orWhereHas('project', function ($query) use ($user): void {
                            $query->where('owner_id', $user->id)
                                ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id));
                        });
                });
            })
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $normalizedSearch = '%'.strtolower($search).'%';

                $query->where(function ($query) use ($normalizedSearch): void {
                    $query->whereRaw('LOWER(title) LIKE ?', [$normalizedSearch])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$normalizedSearch]);
                });
            })
            ->when($validated['status'] ?? null, function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->when($validated['priority'] ?? null, function ($query, string $priority): void {
                $query->where('priority', $priority);
            })
            ->when($validated['projectId'] ?? null, function ($query, int $projectId): void {
                $query->where('project_id', $projectId);
            })
            ->when($validated['creatorId'] ?? null, function ($query, int $creatorId): void {
                $query->where('creator_id', $creatorId);
            })
            ->when($validated['assigneeId'] ?? null, function ($query, int $assigneeId): void {
                $query->where('assignee_id', $assigneeId);
            })
            ->when($validated['dueFrom'] ?? null, function ($query, string $dueFrom): void {
                $query->whereDate('due_date', '>=', $dueFrom);
            })
            ->when($validated['dueUntil'] ?? null, function ($query, string $dueUntil): void {
                $query->whereDate('due_date', '<=', $dueUntil);
            })
            ->when(array_key_exists('hasDescription', $validated), function ($query) use ($request): void {
                if ($request->boolean('hasDescription')) {
                    $query->whereNotNull('description');

                    return;
                }

                $query->whereNull('description');
            })
            ->orderBy($sortBy, $order)
            ->paginate(perPage: $limit, page: $page)
            ->withQueryString();

        return TaskResource::collection($tasks)
            ->additional([
                'pagination' => [
                    'page' => $tasks->currentPage(),
                    'limit' => $tasks->perPage(),
                    'totalItems' => $tasks->total(),
                    'totalPages' => $tasks->lastPage(),
                ],
            ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $project = Project::query()->findOrFail((int) $validated['project_id']);

        Gate::authorize('create', [Task::class, $project]);

        if (! $request->user()->isAdmin()) {
            $validated['creator_id'] = $request->user()->id;
        }

        $task = Task::query()->create($validated);

        return (new TaskResource($task->load(['project', 'creator', 'assignee'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource(
            $task->load(['project', 'creator', 'assignee', 'comments.author', 'attachments.uploader', 'assignedUsers'])
        );
    }

    public function status(UpdateTaskStatusRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }

    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        $tasks = Task::query()
            ->whereKey(collect($request->validated('tasks'))->pluck('id'))
            ->get()
            ->keyBy('id');

        foreach ($request->validated('tasks') as $taskOrder) {
            Gate::authorize('update', $tasks->get($taskOrder['id']));
        }

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
        Gate::authorize('view', $task);

        return CommentResource::collection(
            $task->comments()
                ->with('author')
                ->latest()
                ->get()
        );
    }

    public function attachments(Task $task): AnonymousResourceCollection
    {
        Gate::authorize('view', $task);

        return AttachmentResource::collection(
            $task->attachments()
                ->with('uploader')
                ->latest()
                ->get()
        );
    }

    public function activity(Task $task): AnonymousResourceCollection
    {
        Gate::authorize('view', $task);

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
        Gate::authorize('update', $task);

        $validated = $request->validated();
        $user = User::query()->findOrFail((int) $validated['user_id']);

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
        Gate::authorize('update', $task);

        if (! $task->assignedUsers()->whereKey($user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND, 'User is not assigned to this task.');
        }

        $task->assignedUsers()->detach($user);

        return response()->noContent();
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }

    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
