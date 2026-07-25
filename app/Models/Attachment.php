<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $file_name
 * @property string $file_url
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property int $task_id
 * @property int $uploader_id
 * @property Carbon|null $created_at
 * @property-read Task $task
 * @property-read User $uploader
 *
 * @method bool|null delete()
 */
class Attachment extends Model
{
    protected $fillable = [
        'file_name',
        'file_url',
        'file_size',
        'mime_type',
        'task_id',
        'uploader_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
