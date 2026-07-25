<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProjectTeamPivot $pivot
 *
 * @method bool|null delete()
 */
class Team extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @return BelongsToMany<User, $this, UserRelationshipPivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->using(UserRelationshipPivot::class)
            ->withPivot(['role', 'joined_at']);
    }

    /**
     * @return BelongsToMany<Project, $this, ProjectTeamPivot>
     */
    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_teams')
            ->using(ProjectTeamPivot::class)
            ->withPivot(['assigned_at']);
    }
}
