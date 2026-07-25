<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Export */
class ExportResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'fileName' => $this->file_name,
            'fileUrl' => $this->file_url,
            'filters' => $this->filters,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
