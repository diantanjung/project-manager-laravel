<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\StoreTeamRequest;
use App\Http\Requests\Api\V1\Teams\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TeamResource::collection(
            Team::query()
                ->latest()
                ->get()
        );
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::query()->create($request->validated());

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Team $team): TeamResource
    {
        return new TeamResource(
            $team->load(['members', 'assignedProjects'])
        );
    }

    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $team->update($request->validated());

        return new TeamResource($team);
    }

    public function destroy(Team $team): Response
    {
        $team->delete();

        return response()->noContent();
    }
}
