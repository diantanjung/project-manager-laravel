<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

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
        $disk = (string) config('filesystems.attachment_disk', 'r2');
        $downloadUrlLifetime = (int) config('filesystems.attachment_download_url_lifetime_minutes', 10);

        return [
            'id' => $this->id,
            'taskId' => $this->task_id,
            'uploaderId' => $this->uploader_id,
            'disk' => $disk,
            'path' => $this->file_url,
            'downloadUrl' => URL::temporarySignedRoute(
                'api.v1.attachments.download',
                now()->addMinutes($downloadUrlLifetime),
                ['attachment' => $this->id],
            ),
            'originalName' => $this->file_name,
            'mimeType' => $this->mime_type,
            'size' => $this->file_size,
            'fileName' => $this->file_name,
            'fileUrl' => $this->file_url,
            'fileSize' => $this->file_size,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'task' => new TaskResource($this->whenLoaded('task')),
            'uploader' => new UserResource($this->whenLoaded('uploader')),
        ];
    }
}
