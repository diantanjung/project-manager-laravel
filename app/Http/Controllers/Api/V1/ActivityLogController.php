<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ActivityLogResource::collection(
            ActivityLog::query()
                ->with('actor')
                ->latest('created_at')
                ->get()
        );
    }
}
