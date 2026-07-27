<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property string|null $avatar_url
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UserRelationshipPivot $pivot
 *
 * @method bool|null delete()
 */
#[Fillable(['name', 'email', 'password', 'role', 'avatar_url', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'authTokens', 'refreshTokens'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::TeamMember->value,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AuthToken, $this>
     */
    public function authTokens(): HasMany
    {
        return $this->hasMany(AuthToken::class);
    }

    /**
     * @return HasMany<RefreshToken, $this>
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * @return BelongsToMany<Team, $this, UserRelationshipPivot>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->using(UserRelationshipPivot::class)
            ->withPivot(['role', 'joined_at']);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploader_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canAccessProject(Project $project): bool
    {
        if ($this->isAdmin() || $project->owner_id === $this->id) {
            return true;
        }

        return $project->assignedTeams()
            ->whereHas('members', fn ($query) => $query->whereKey($this->id))
            ->exists()
            || $project->tasks()
                ->where(function ($query): void {
                    $query->where('creator_id', $this->id)
                        ->orWhere('assignee_id', $this->id)
                        ->orWhereHas('assignedUsers', fn ($query) => $query->whereKey($this->id));
                })
                ->exists();
    }

    public function canManageProject(Project $project): bool
    {
        if ($this->isAdmin() || $project->owner_id === $this->id) {
            return true;
        }

        return $project->assignedTeams()
            ->whereHas('members', function ($query): void {
                $query->whereKey($this->id)
                    ->whereIn('team_members.role', ['owner', 'admin']);
            })
            ->exists();
    }

    public function canAccessTask(Task $task): bool
    {
        if ($this->isAdmin() || $task->creator_id === $this->id || $task->assignee_id === $this->id) {
            return true;
        }

        return $task->assignedUsers()
            ->whereKey($this->id)
            ->exists()
            || $this->canAccessProject($task->project()->firstOrFail());
    }

    public function canManageTask(Task $task): bool
    {
        return $this->isAdmin()
            || $task->creator_id === $this->id
            || $task->assignee_id === $this->id
            || $this->canManageProject($task->project()->firstOrFail());
    }

    public function canAccessTeam(Team $team): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $team->members()
            ->whereKey($this->id)
            ->exists()
            || $team->assignedProjects()
                ->where('owner_id', $this->id)
                ->exists();
    }

    public function canManageTeam(Team $team): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $team->members()
            ->whereKey($this->id)
            ->whereIn('team_members.role', ['owner', 'admin'])
            ->exists();
    }
}
