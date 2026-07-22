<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

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
            $task->load(['project', 'creator', 'assignee', 'comments.author', 'attachments.uploader'])
        );
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
