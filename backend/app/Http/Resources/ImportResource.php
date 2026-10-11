<?php

namespace App\Http\Resources;

use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'original_filename' => $this->original_filename,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'progress' => $this->progressPercent(),
            'created_count' => $this->created_count,
            'updated_count' => $this->updated_count,
            'failed_count' => $this->failed_count,
            'errors' => $this->errors ?? [],
            'warnings' => $this->warnings ?? [],
            'failure_reason' => $this->failure_reason,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
