<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAcquisitionReview extends Model
{
    protected $fillable = [
        'asset_acquisition_id', 'reviewed_by', 'decision',
        'owner_entity_before', 'owner_entity_after', 'review_note',
    ];

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(AssetAcquisition::class, 'asset_acquisition_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}