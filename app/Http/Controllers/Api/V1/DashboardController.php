<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);
        Gate::authorize('viewAny', Task::class);

        $user = request()->user();
        $applyTaskScope = function ($query) use ($user): void {
            if ($user->isAdmin()) {
                return;
            }

            $query->where(function ($query) use ($user): void {
                $query->where('creator_id', $user->id)
                    ->orWhere('assignee_id', $user->id)
                    ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id))
                    ->orWhereHas('project', function ($query) use ($user): void {
                        $query->where('owner_id', $user->id)
                            ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id));
                    });
            });
        };

        $applyProjectScope = function ($query) use ($user): void {
            if ($user->isAdmin()) {
                return;
            }

            $query->where(function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id))
                    ->orWhereHas('tasks', function ($query) use ($user): void {
                        $query->where('creator_id', $user->id)
                            ->orWhere('assignee_id', $user->id)
                            ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id));
                    });
            });
        };

        $taskCounts = [];

        foreach (TaskStatus::cases() as $status) {
            $taskCounts[$status->value] = Task::query()
                ->tap($applyTaskScope)
                ->where('status', $status->value)
                ->count('*');
        }

        return response()->json([
            'totalActiveProjects' => Project::query()
                ->tap($applyProjectScope)
                ->count('*'),
            'taskCountPerStatus' => $taskCounts,
            'overdueTaskCount' => Task::query()
                ->tap($applyTaskScope)
                ->whereDate('due_date', '<', today(), 'and')
                ->where('status', '!=', TaskStatus::Done->value)
                ->count('*'),
            'workloadPerMember' => Task::query()
                ->tap($applyTaskScope)
                ->selectRaw('assignee_id, count(*) as task_count')
                ->groupBy('assignee_id')
                ->pluck('task_count', 'assignee_id'),
            'recentlyUpdatedTasks' => TaskResource::collection(
                Task::query()
                    ->with(['project', 'creator', 'assignee'])
                    ->tap($applyTaskScope)
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
            ),
        ]);
    }
}
