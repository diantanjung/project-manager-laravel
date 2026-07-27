<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attachments\StoreAttachmentRequest;
use App\Http\Requests\Api\V1\Attachments\UpdateAttachmentRequest;
use App\Http\Resources\Api\V1\AttachmentResource;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Attachment::class);

        $user = request()->user();

        return AttachmentResource::collection(
            Attachment::query()
                ->with('uploader')
                ->when(! $user->isAdmin(), function ($query) use ($user): void {
                    $query->whereHas('task', function ($query) use ($user): void {
                        $query->where('creator_id', $user->id)
                            ->orWhere('assignee_id', $user->id)
                            ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($user->id))
                            ->orWhereHas('project', function ($query) use ($user): void {
                                $query->where('owner_id', $user->id)
                                    ->orWhereHas('assignedTeams.members', fn ($query) => $query->whereKey($user->id));
                            });
                    });
                })
                ->latest()
                ->get()
        );
    }

    public function store(StoreAttachmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $task = Task::query()->findOrFail((int) $validated['task_id']);

        Gate::authorize('create', [Attachment::class, $task]);

        if (! $request->user()->isAdmin()) {
            $validated['uploader_id'] = $request->user()->id;
        }

        if ($request->hasFile('file')) {
            $attachmentDiskName = (string) config('filesystems.attachment_disk', 'r2');
            $uploadedFile = $request->file('file');
            $path = $uploadedFile->store('attachments/'.$validated['task_id'], $attachmentDiskName);

            if ($path === false) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Attachment upload failed.');
            }

            $validated = [
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_url' => $path,
                'file_size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'task_id' => $validated['task_id'],
                'uploader_id' => $validated['uploader_id'],
            ];
        }

        $attachment = Attachment::query()->create($validated);

        return (new AttachmentResource($attachment->load('uploader')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Attachment $attachment): AttachmentResource
    {
        Gate::authorize('view', $attachment);

        return new AttachmentResource(
            $attachment->load(['task', 'uploader'])
        );
    }

    public function download(Attachment $attachment): RedirectResponse|StreamedResponse
    {
        if (! filter_var($attachment->file_url, FILTER_VALIDATE_URL)) {
            /** @var FilesystemAdapter $attachmentDisk */
            $attachmentDisk = Storage::disk((string) config('filesystems.attachment_disk', 'r2'));

            return $attachmentDisk->response(
                $attachment->file_url,
                $attachment->file_name,
                [
                    'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
                ],
                'inline'
            );
        }

        return redirect()->away($attachment->file_url);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment): AttachmentResource
    {
        Gate::authorize('update', $attachment);

        $attachment->update($request->validated());

        return new AttachmentResource($attachment->load('uploader'));
    }

    public function destroy(Attachment $attachment): Response
    {
        Gate::authorize('delete', $attachment);

        if (! filter_var($attachment->file_url, FILTER_VALIDATE_URL)) {
            Storage::disk((string) config('filesystems.attachment_disk', 'r2'))->delete($attachment->file_url);
        }

        $attachment->delete();

        return response()->noContent();
    }
}
