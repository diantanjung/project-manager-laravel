<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\IndexUserRequest;
use App\Http\Requests\Api\V1\Users\StoreUserAvatarRequest;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sortBy = $validated['sortBy'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        /** @var LengthAwarePaginator<int, User> $users */
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'avatar_url',
                'is_active',
                'email_verified_at',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy($sortBy, $order)
            ->paginate(perPage: $limit, page: $page)
            ->withQueryString();

        return UserResource::collection($users)
            ->additional([
                'pagination' => [
                    'page' => $users->currentPage(),
                    'limit' => $users->perPage(),
                    'totalItems' => $users->total(),
                    'totalPages' => $users->lastPage(),
                ],
            ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }

    public function avatar(StoreUserAvatarRequest $request, User $user): UserResource
    {
        $avatarUrl = $request->validated('avatar_url');

        if ($request->hasFile('avatar')) {
            $avatarDiskName = (string) config('filesystems.avatar_disk', 'r2');
            $path = $request->file('avatar')->store('avatars', $avatarDiskName);

            if ($path === false) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Avatar upload failed.');
            }

            /** @var FilesystemAdapter $avatarDisk */
            $avatarDisk = Storage::disk($avatarDiskName);
            $avatarUrl = $avatarDisk->url($path);
        }

        $user->update(['avatar_url' => $avatarUrl]);

        return new UserResource($user);
    }

    public function tasks(User $user): AnonymousResourceCollection
    {
        return TaskResource::collection(
            $user->assignedTasks()
                ->with(['project', 'creator', 'assignee'])
                ->latest()
                ->get()
        );
    }
}
