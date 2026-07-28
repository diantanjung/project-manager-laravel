<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class DashboardTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'priority' => $this->priority?->value,
            'projectId' => $this->project_id,
            'assigneeId' => $this->assignee_id,
            'dueDate' => $this->due_date?->toDateString(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'project' => $this->whenLoaded('project', fn (): array => [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ]),
        ];
    }
}
