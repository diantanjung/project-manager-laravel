<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attachments\StoreAttachmentRequest;
use App\Http\Requests\Api\V1\Attachments\UpdateAttachmentRequest;
use App\Http\Resources\Api\V1\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttachmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AttachmentResource::collection(
            Attachment::query()
                ->with('uploader')
                ->latest()
                ->get()
        );
    }

    public function store(StoreAttachmentRequest $request): JsonResponse
    {
        $attachment = Attachment::query()->create($request->validated());

        return (new AttachmentResource($attachment->load('uploader')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Attachment $attachment): AttachmentResource
    {
        return new AttachmentResource(
            $attachment->load(['task', 'uploader'])
        );
    }

    public function download(Attachment $attachment): RedirectResponse
    {
        return redirect()->away($attachment->file_url);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment): AttachmentResource
    {
        $attachment->update($request->validated());

        return new AttachmentResource($attachment->load('uploader'));
    }

    public function destroy(Attachment $attachment): Response
    {
        $attachment->delete();

        return response()->noContent();
    }
}
