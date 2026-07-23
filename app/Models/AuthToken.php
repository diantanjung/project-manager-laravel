<?php

namespace App\Models;

use App\Enums\AuthTokenType;
use Database\Factories\AuthTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property AuthTokenType $type
 * @property string $token_hash
 * @property Carbon|null $last_used_at
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Hidden(['token_hash'])]
class AuthToken extends Model
{
    /** @use HasFactory<AuthTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'token_hash',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AuthTokenType::class,
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    public function markRevoked(): void
    {
        if ($this->revoked_at !== null) {
            return;
        }

        $this->forceFill(['revoked_at' => now()])->save();
    }
}
