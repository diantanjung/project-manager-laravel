<?php

use App\Models\ChecklistItem;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\withToken;

test('checklist items can be managed through the api', function () {
    $user = User::factory()->create();
    $task = domainApiTask(creator: $user);
    $token = domainApiAccessToken($user);

    $itemId = withToken($token)
        ->postJson("/api/v1/tasks/{$task->id}/checklist-items", [
            'title' => 'Confirm acceptance criteria',
            'position' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.taskId', $task->id)
        ->assertJsonPath('data.title', 'Confirm acceptance criteria')
        ->json('data.id');

    $item = ChecklistItem::query()->findOrFail($itemId);

    withToken($token)
        ->patchJson("/api/v1/checklist-items/{$item->id}", [
            'is_checked' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.isChecked', true);

    expect($item->refresh()->is_checked)->toBeTrue();

    withToken($token)
        ->deleteJson("/api/v1/checklist-items/{$item->id}")
        ->assertNoContent();

    assertModelMissing($item);
});
