<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\AssignProjectTeamRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->with('owner')
            ->latest()
            ->get();

        return ProjectResource::collection($projects)
            ->additional([
                'pagination' => [
                    'page' => 1,
                    'limit' => $projects->count(),
                    'totalItems' => $projects->count(),
                    'totalPages' => 1,
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

    public function tasks(Project $project): AnonymousResourceCollection
    {
        return TaskResource::collection(
            $project->tasks()
                ->with(['creator', 'assignee'])
                ->orderBy('position')
                ->latest()
                ->get()
        );
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
