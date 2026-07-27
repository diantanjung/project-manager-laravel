<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Project::class);
        Gate::authorize('viewAny', Task::class);

        $user = request()->user();

        return ActivityLogResource::collection(
            ActivityLog::query()
                ->with('actor')
                ->when(! $user->isAdmin(), function ($query) use ($user): void {
                    $query->where(function ($query) use ($user): void {
                        $query->where(function ($query) use ($user): void {
                            $query->where('entity_type', Project::class)
                                ->whereIn('entity_id', Project::query()
                                    ->select('id')
                                    ->where('owner_id', $user->id)
                                    ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id)));
                        })->orWhere(function ($query) use ($user): void {
                            $query->where('entity_type', Task::class)
                                ->whereIn('entity_id', Task::query()
                                    ->select('id')
                                    ->where('creator_id', $user->id)
                                    ->orWhere('assignee_id', $user->id)
                                    ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id))
                                    ->orWhereHas('project', function ($query) use ($user): void {
                                        $query->where('owner_id', $user->id)
                                            ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id));
                                    }));
                        });
                    });
                })
                ->latest('created_at')
                ->get()
        );
    }
}
