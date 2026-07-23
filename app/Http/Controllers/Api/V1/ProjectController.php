<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
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
