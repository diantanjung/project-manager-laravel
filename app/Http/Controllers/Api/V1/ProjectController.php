<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\AssignProjectTeamRequest;
use App\Http\Requests\Api\V1\Projects\IndexProjectRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Requests\Api\V1\Tasks\IndexTaskRequest;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sortBy = $validated['sortBy'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        /** @var LengthAwarePaginator<int, Project> $projects */
        $projects = Project::query()
            ->with('owner')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $normalizedSearch = '%'.strtolower($search).'%';

                $query->where(function ($query) use ($normalizedSearch): void {
                    $query->whereRaw('LOWER(name) LIKE ?', [$normalizedSearch])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$normalizedSearch]);
                });
            })
            ->when($validated['ownerId'] ?? null, function ($query, int $ownerId): void {
                $query->where('owner_id', $ownerId);
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

        return ProjectResource::collection($projects)
            ->additional([
                'pagination' => [
                    'page' => $projects->currentPage(),
                    'limit' => $projects->perPage(),
                    'totalItems' => $projects->total(),
                    'totalPages' => $projects->lastPage(),
                ],
            ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::query()->create($request->validated());

        return (new ProjectResource($project->load('owner')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        return new ProjectResource(
            $project->load(['owner', 'assignedTeams'])
        );
    }

    public function tasks(IndexTaskRequest $request, Project $project): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sortBy = $validated['sortBy'] ?? 'position';
        $order = $validated['order'] ?? 'asc';

        /** @var LengthAwarePaginator<int, Task> $tasks */
        $tasks = $project->tasks()
            ->with(['project.owner', 'creator', 'assignee'])
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

    public function activity(Project $project): AnonymousResourceCollection
    {
        $taskIds = $project->tasks()->pluck('id');

        return ActivityLogResource::collection(
            ActivityLog::query()
                ->with('actor')
                ->where(function ($query) use ($project, $taskIds): void {
                    $query->where(function ($query) use ($project): void {
                        $query->where('entity_type', Project::class)
                            ->where('entity_id', $project->id);
                    })->orWhere(function ($query) use ($taskIds): void {
                        $query->where('entity_type', Task::class)
                            ->whereIn('entity_id', $taskIds);
                    });
                })
                ->latest('created_at')
                ->get()
        );
    }

    public function summary(Project $project): JsonResponse
    {
        $statusCounts = $project->tasks()
            ->selectRaw('status, count(*) as task_count')
            ->groupBy('status')
            ->pluck('task_count', 'status');

        return response()->json([
            'projectId' => $project->id,
            'taskCountPerStatus' => $statusCounts,
            'totalTasks' => $project->tasks()->count(),
            'teamCount' => $project->assignedTeams()->count(),
        ]);
    }

    public function assignTeam(AssignProjectTeamRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();
        $team = Team::query()->findOrFail((int) $validated['team_id']);

        if ($project->assignedTeams()->whereKey($team->id)->exists()) {
            return response()->json([
                'message' => 'Team is already assigned to this project.',
            ], Response::HTTP_CONFLICT);
        }

        $project->assignedTeams()->attach($team, [
            'assigned_at' => now(),
        ]);

        return (new ProjectResource($project->load(['owner', 'assignedTeams'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function removeTeam(Project $project, Team $team): Response
    {
        if (! $project->assignedTeams()->whereKey($team->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND, 'Team is not assigned to this project.');
        }

        $project->assignedTeams()->detach($team);

        return response()->noContent();
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project->update($request->validated());

        return new ProjectResource($project->load('owner'));
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }
}
