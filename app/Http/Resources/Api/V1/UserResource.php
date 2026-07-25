<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'avatarUrl' => $this->avatar_url,
            'isActive' => $this->is_active,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'lastLoginAt' => $this->last_login_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'membership' => $this->whenPivotLoaded('team_members', fn (): array => [
                'role' => $this->pivot->role,
                'joinedAt' => $this->pivot->joined_at,
            ]),
            'taskAssignment' => $this->whenPivotLoaded('task_assignments', fn (): array => [
                'assignedBy' => $this->pivot->assigned_by,
                'createdAt' => $this->pivot->created_at?->toIso8601String(),
                'updatedAt' => $this->pivot->updated_at?->toIso8601String(),
            ]),
        ];
    }
}
