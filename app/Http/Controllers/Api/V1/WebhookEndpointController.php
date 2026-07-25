<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WebhookEndpoints\StoreWebhookEndpointRequest;
use App\Http\Requests\Api\V1\WebhookEndpoints\UpdateWebhookEndpointRequest;
use App\Http\Resources\Api\V1\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WebhookEndpointController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return WebhookEndpointResource::collection(
            WebhookEndpoint::query()
                ->latest()
                ->get()
        );
    }

    public function store(StoreWebhookEndpointRequest $request): JsonResponse
    {
        $endpoint = WebhookEndpoint::query()->create($request->validated());

        return (new WebhookEndpointResource($endpoint))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateWebhookEndpointRequest $request, WebhookEndpoint $webhookEndpoint): WebhookEndpointResource
    {
        $webhookEndpoint->update($request->validated());

        return new WebhookEndpointResource($webhookEndpoint);
    }

    public function destroy(WebhookEndpoint $webhookEndpoint): Response
    {
        $webhookEndpoint->delete();

        return response()->noContent();
    }
}
