<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Auth\AuthTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function domainApiAccessToken(?User $user = null): string
{
    $user ??= User::factory()->create();

    return app(AuthTokenService::class)->issueTokenPair($user)['accessToken'];
}

function domainApiProject(?User $owner = null): Project
{
    $owner ??= User::factory()->create();

    return Project::query()->create([
        'name' => 'Website redesign',
        'description' => 'Refresh the marketing site',
        'owner_id' => $owner->id,
    ]);
}

function domainApiTask(?Project $project = null, ?User $creator = null, ?User $assignee = null): Task
{
    $creator ??= User::factory()->create();
    $assignee ??= User::factory()->create();
    $project ??= domainApiProject($creator);

    return Task::withoutEvents(fn (): Task => Task::query()->create([
        'title' => 'Create wireframes',
        'description' => 'Initial homepage wireframes',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'project_id' => $project->id,
        'creator_id' => $creator->id,
        'assignee_id' => $assignee->id,
        'due_date' => '2026-08-01',
    ]));
}
