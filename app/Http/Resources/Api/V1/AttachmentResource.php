<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attachment */
class AttachmentResource extends JsonResource
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
            'fileName' => $this->file_name,
            'fileUrl' => $this->file_url,
            'fileSize' => $this->file_size,
            'mimeType' => $this->mime_type,
            'taskId' => $this->task_id,
            'uploaderId' => $this->uploader_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'task' => new TaskResource($this->whenLoaded('task')),
            'uploader' => new UserResource($this->whenLoaded('uploader')),
        ];
    }
}
