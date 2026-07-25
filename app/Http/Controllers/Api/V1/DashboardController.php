<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $taskCounts = [];

        foreach (TaskStatus::cases() as $status) {
            $taskCounts[$status->value] = Task::query()->where('status', $status->value)->count('*');
        }

        return response()->json([
            'totalActiveProjects' => Project::query()->count('*'),
            'taskCountPerStatus' => $taskCounts,
            'overdueTaskCount' => Task::query()
                ->whereDate('due_date', '<', today(), 'and')
                ->where('status', '!=', TaskStatus::Done->value)
                ->count('*'),
            'workloadPerMember' => Task::query()
                ->selectRaw('assignee_id, count(*) as task_count')
                ->groupBy('assignee_id')
                ->pluck('task_count', 'assignee_id'),
            'recentlyUpdatedTasks' => TaskResource::collection(
                Task::query()
                    ->with(['project', 'creator', 'assignee'])
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
            ),
        ]);
    }
}
