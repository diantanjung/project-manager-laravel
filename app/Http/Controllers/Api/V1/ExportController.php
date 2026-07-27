<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Exports\StoreProjectReportExportRequest;
use App\Http\Resources\Api\V1\ExportResource;
use App\Models\Export;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    public function projectReport(StoreProjectReportExportRequest $request): JsonResponse
    {
        Gate::authorize('create', Export::class);

        $validated = $request->validated();
        $project = Project::query()->findOrFail((int) $validated['project_id']);

        Gate::authorize('view', $project);

        $export = Export::query()->create([
            'type' => 'project-report',
            'status' => 'completed',
            'file_name' => 'project-'.$project->id.'-report.csv',
            'file_url' => url('/api/v1/exports/pending'),
            'filters' => [
                'project_id' => $project->id,
                'format' => $validated['format'] ?? 'csv',
            ],
            'created_by' => $request->user()->id,
        ]);

        $export->update([
            'file_url' => url('/api/v1/exports/'.$export->id),
        ]);

        return (new ExportResource($export))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Export $export): ExportResource
    {
        Gate::authorize('view', $export);

        return new ExportResource($export->load('creator'));
    }
}
