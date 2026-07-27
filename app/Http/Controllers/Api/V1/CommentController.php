<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comments\StoreCommentRequest;
use App\Http\Requests\Api\V1\Comments\UpdateCommentRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Comment::class);

        $user = request()->user();

        return CommentResource::collection(
            Comment::query()
                ->with('author')
                ->when(! $user->isAdmin(), function ($query) use ($user): void {
                    $query->whereHas('task', function ($query) use ($user): void {
                        $query->where('creator_id', $user->id)
                            ->orWhere('assignee_id', $user->id)
                            ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id))
                            ->orWhereHas('project', function ($query) use ($user): void {
                                $query->where('owner_id', $user->id)
                                    ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id));
                            });
                    });
                })
                ->latest()
                ->get()
        );
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $task = Task::query()->findOrFail((int) $validated['task_id']);

        Gate::authorize('create', [Comment::class, $task]);

        if (! $request->user()->isAdmin()) {
            $validated['author_id'] = $request->user()->id;
        }

        $comment = Comment::query()->create($validated);

        return (new CommentResource($comment->load('author')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Comment $comment): CommentResource
    {
        Gate::authorize('view', $comment);

        return new CommentResource(
            $comment->load(['task', 'author'])
        );
    }

    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        Gate::authorize('update', $comment);

        $comment->update($request->validated());

        return new CommentResource($comment->load('author'));
    }

    public function destroy(Comment $comment): Response
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
