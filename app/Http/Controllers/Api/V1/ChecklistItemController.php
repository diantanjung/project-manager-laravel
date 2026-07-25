<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChecklistItems\StoreChecklistItemRequest;
use App\Http\Requests\Api\V1\ChecklistItems\UpdateChecklistItemRequest;
use App\Http\Resources\Api\V1\ChecklistItemResource;
use App\Models\ChecklistItem;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ChecklistItemController extends Controller
{
    public function store(StoreChecklistItemRequest $request, Task $task): JsonResponse
    {
        $item = ChecklistItem::query()->create($request->validated());

        return (new ChecklistItemResource($item))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $item): ChecklistItemResource
    {
        $item->update($request->validated());

        return new ChecklistItemResource($item);
    }

    public function destroy(ChecklistItem $item): Response
    {
        $item->delete();

        return response()->noContent();
    }
}
