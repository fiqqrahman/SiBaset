<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['asset_id', 'asset_acquisition_id', 'actor_id', 'event_type', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(AssetAcquisition::class, 'asset_acquisition_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}