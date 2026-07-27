<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\AddTeamMemberRequest;
use App\Http\Requests\Api\V1\Teams\IndexTeamRequest;
use App\Http\Requests\Api\V1\Teams\StoreTeamRequest;
use App\Http\Requests\Api\V1\Teams\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(IndexTeamRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Team::class);

        $validated = $request->validated();
        $user = $request->user();
        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sortBy = $validated['sortBy'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        /** @var LengthAwarePaginator<int, Team> $teams */
        $teams = Team::query()
            ->select([
                'id',
                'name',
                'description',
                'created_at',
                'updated_at',
            ])
            ->when(! $user->isAdmin(), function ($query) use ($user): void {
                $query->where(function ($query) use ($user): void {
                    $query->whereHas('members', fn ($query) => $query->whereKey($user->id))
                        ->orWhereHas('assignedProjects', fn ($query) => $query->where('owner_id', $user->id));
                });
            })
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $normalizedSearch = '%'.strtolower($search).'%';

                $query->where(function ($query) use ($normalizedSearch): void {
                    $query->whereRaw('LOWER(name) LIKE ?', [$normalizedSearch])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$normalizedSearch]);
                });
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

        return TeamResource::collection($teams)
            ->additional([
                'pagination' => [
                    'page' => $teams->currentPage(),
                    'limit' => $teams->perPage(),
                    'totalItems' => $teams->total(),
                    'totalPages' => $teams->lastPage(),
                ],
            ]);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        Gate::authorize('create', Team::class);

        $team = Team::query()->create($request->validated());
        $team->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return (new TeamResource($team->load('members')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Team $team): TeamResource
    {
        Gate::authorize('view', $team);

        return new TeamResource(
            $team->load(['members', 'assignedProjects'])
        );
    }

    public function members(Team $team): AnonymousResourceCollection
    {
        Gate::authorize('view', $team);

        $members = $team->members()
            ->orderByPivotDesc('joined_at')
            ->get();

        return UserResource::collection($members);
    }

    public function addMember(AddTeamMemberRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $validated = $request->validated();

        $user = User::query()->findOrFail((int) $validated['user_id']);

        if ($team->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'User is already a member of this team.',
            ], Response::HTTP_CONFLICT);
        }

        $team->members()->attach($user, [
            'role' => $validated['role'] ?? 'member',
            'joined_at' => now(),
        ]);

        return (new TeamResource($team->load('members')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function removeMember(Team $team, User $user): Response
    {
        Gate::authorize('update', $team);

        if (! $team->members()->whereKey($user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND, 'User is not a member of this team.');
        }

        $team->members()->detach($user);

        return response()->noContent();
    }

    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        Gate::authorize('update', $team);

        $team->update($request->validated());

        return new TeamResource($team);
    }

    public function destroy(Team $team): Response
    {
        Gate::authorize('delete', $team);

        $team->members()->detach();
        $team->assignedProjects()->detach();
        $team->delete();

        return response()->noContent();
    }
}
