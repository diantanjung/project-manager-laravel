<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WebhookDeliveryResource;
use App\Models\WebhookDelivery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookDeliveryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return WebhookDeliveryResource::collection(
            WebhookDelivery::query()
                ->with('endpoint')
                ->latest()
                ->get()
        );
    }
}
