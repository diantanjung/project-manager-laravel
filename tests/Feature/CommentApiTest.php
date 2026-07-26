<?php

use App\Models\Comment;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\withToken;

test('comments can be managed through the api', function () {
    $author = User::factory()->create();
    $task = domainApiTask(creator: $author);
    $token = domainApiAccessToken($author);

    $commentId = withToken($token)
        ->postJson('/api/v1/comments', [
            'content' => 'Looks ready for review.',
            'task_id' => $task->id,
            'author_id' => $author->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Looks ready for review.')
        ->json('data.id');

    $comment = Comment::query()->findOrFail($commentId);

    withToken($token)
        ->patchJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Looks ready for QA.',
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Looks ready for QA.');

    withToken($token)
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertNoContent();

    assertModelMissing($comment);
});
