<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    protected $fillable = [
        'asset_acquisition_id', 'asset_id', 'uploaded_by', 'document_kind',
        'original_name', 'storage_path', 'mime_type', 'size_bytes',
    ];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(AssetAcquisition::class, 'asset_acquisition_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}