<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comments\StoreCommentRequest;
use App\Http\Requests\Api\V1\Comments\UpdateCommentRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CommentResource::collection(
            Comment::query()
                ->with('author')
                ->latest()
                ->get()
        );
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $comment = Comment::query()->create($request->validated());

        return (new CommentResource($comment->load('author')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Comment $comment): CommentResource
    {
        return new CommentResource(
            $comment->load(['task', 'author'])
        );
    }

    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        $comment->update($request->validated());

        return new CommentResource($comment->load('author'));
    }

    public function destroy(Comment $comment): Response
    {
        $comment->delete();

        return response()->noContent();
    }
}
