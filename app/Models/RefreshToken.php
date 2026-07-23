<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $hash_token
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property bool|null $is_revoked
 * @property-read User $user
 */
#[Hidden(['hash_token'])]
class RefreshToken extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'hash_token',
        'expires_at',
        'is_revoked',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_revoked' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRevoked(): void
    {
        if ($this->is_revoked === true) {
            return;
        }

        $this->forceFill(['is_revoked' => true])->save();
    }
}
