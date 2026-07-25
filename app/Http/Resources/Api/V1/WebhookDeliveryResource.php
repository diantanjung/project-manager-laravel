<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebhookDelivery */
class WebhookDeliveryResource extends JsonResource
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
            'webhookEndpointId' => $this->webhook_endpoint_id,
            'eventType' => $this->event_type,
            'payload' => $this->payload,
            'status' => $this->status,
            'attemptCount' => $this->attempt_count,
            'lastError' => $this->last_error,
            'deliveredAt' => $this->delivered_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'endpoint' => new WebhookEndpointResource($this->whenLoaded('endpoint')),
        ];
    }
}
