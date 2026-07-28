<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Http\Resources\Api\V1\DashboardTaskResource;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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

        $baseTaskListQuery = fn () => Task::query()
            ->select([
                'id',
                'title',
                'status',
                'priority',
                'project_id',
                'assignee_id',
                'due_date',
                'created_at',
                'updated_at',
            ])
            ->with('project:id,name')
            ->tap($applyTaskScope);

        $latestUpdatesQuery = ActivityLog::query()
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
            ->limit(5);

        $payload = Cache::remember("dashboard:user:{$user->id}", 15, function () use ($applyProjectScope, $applyTaskScope, $baseTaskListQuery, $latestUpdatesQuery): array {
            $rawTaskCounts = Task::query()
                ->tap($applyTaskScope)
                ->selectRaw('status, count(*) as task_count')
                ->groupBy('status')
                ->pluck('task_count', 'status')
                ->all();

            $taskCounts = [];

            foreach (TaskStatus::cases() as $status) {
                $taskCounts[$status->value] = (int) ($rawTaskCounts[$status->value] ?? 0);
            }

            $todoTaskCount = $taskCounts[TaskStatus::Todo->value];
            $doingTaskCount = $taskCounts[TaskStatus::InProgress->value];
            $activeTaskCount = $todoTaskCount + $doingTaskCount;
            $today = today();
            $dueSoonUntil = Carbon::today()->addDays(7);
            $overdueTaskCount = Task::query()
                ->tap($applyTaskScope)
                ->whereDate('due_date', '<', $today, 'and')
                ->where('status', '!=', TaskStatus::Done->value)
                ->count('*');

            return [
                'totalActiveProjects' => Project::query()
                    ->tap($applyProjectScope)
                    ->count('*'),
                'taskCountPerStatus' => $taskCounts,
                'activeProgress' => [
                    'doing' => $doingTaskCount,
                    'todo' => $todoTaskCount,
                    'total' => $activeTaskCount,
                    'ratio' => $activeTaskCount > 0 ? round($doingTaskCount / $activeTaskCount, 4) : 0.0,
                    'percentage' => $activeTaskCount > 0 ? round(($doingTaskCount / $activeTaskCount) * 100, 2) : 0.0,
                ],
                'inReview' => $taskCounts[TaskStatus::Review->value],
                'dueSoon' => Task::query()
                    ->tap($applyTaskScope)
                    ->whereBetween('due_date', [$today, $dueSoonUntil], 'and', false)
                    ->where('status', '!=', TaskStatus::Done->value)
                    ->count('*'),
                'overdue' => $overdueTaskCount,
                'overdueTaskCount' => $overdueTaskCount,
                'workloadPerMember' => Task::query()
                    ->tap($applyTaskScope)
                    ->selectRaw('assignee_id, count(*) as task_count')
                    ->groupBy('assignee_id')
                    ->pluck('task_count', 'assignee_id')
                    ->map(fn (int $taskCount): int => $taskCount)
                    ->all(),
                'recentlyUpdatedTasks' => DashboardTaskResource::collection(
                    $baseTaskListQuery()
                        ->latest('updated_at')
                        ->limit(5)
                        ->get()
                )->resolve(),
                'recentTasks' => DashboardTaskResource::collection(
                    $baseTaskListQuery()
                        ->latest('created_at')
                        ->limit(5)
                        ->get()
                )->resolve(),
                'upcomingDeadlines' => DashboardTaskResource::collection(
                    $baseTaskListQuery()
                        ->whereDate('due_date', '>=', $today)
                        ->where('status', '!=', TaskStatus::Done->value)
                        ->orderBy('due_date')
                        ->orderBy('id')
                        ->limit(5)
                        ->get()
                )->resolve(),
                'highPriorityTasks' => DashboardTaskResource::collection(
                    $baseTaskListQuery()
                        ->whereIn('priority', [TaskPriority::High->value, TaskPriority::Urgent->value])
                        ->where('status', '!=', TaskStatus::Done->value)
                        ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 else 2 end")
                        ->orderBy('due_date')
                        ->limit(5)
                        ->get()
                )->resolve(),
                'latestUpdates' => ActivityLogResource::collection($latestUpdatesQuery->get())->resolve(),
            ];
        });

        return response()->json($payload);
    }
}
