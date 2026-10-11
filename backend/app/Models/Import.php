<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int|null $user_id
 * @property ImportStatus $status
 * @property string $original_filename
 * @property string $file_path
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $created_count
 * @property int $updated_count
 * @property int $failed_count
 * @property list<array{row: int, external_code: string|null, messages: list<string>}>|null $errors
 * @property list<array{row: int, external_code: string|null, message: string}>|null $warnings
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'status', 'original_filename', 'file_path', 'total_rows', 'processed_rows',
    'created_count', 'updated_count', 'failed_count', 'errors', 'warnings', 'failure_reason',
    'started_at', 'finished_at',
])]
class Import extends Model
{
    use HasUuids;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'total_rows' => 0,
        'processed_rows' => 0,
        'created_count' => 0,
        'updated_count' => 0,
        'failed_count' => 0,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): int
    {
        if ($this->status === ImportStatus::Completed) {
            return 100;
        }

        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) min(100, floor($this->processed_rows / $this->total_rows * 100));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'failed_count' => 'integer',
            'errors' => 'array',
            'warnings' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
