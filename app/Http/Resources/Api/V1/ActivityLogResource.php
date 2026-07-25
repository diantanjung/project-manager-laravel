<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
class ActivityLogResource extends JsonResource
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
            'actorId' => $this->actor_id,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'action' => $this->action,
            'before' => $this->before,
            'after' => $this->after,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'createdAt' => $this->created_at?->toIso8601String(),
            'actor' => new UserResource($this->whenLoaded('actor')),
        ];
    }
}
