<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolImportLog extends Model
{
    protected $fillable = [
        'uploaded_by',
        'source_file_name',
        'total_rows',
        'created_count',
        'updated_count',
        'coordinate_warnings',
        'status',
        'summary',
    ];

    protected function casts(): array
    {
        return ['summary' => 'array'];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
