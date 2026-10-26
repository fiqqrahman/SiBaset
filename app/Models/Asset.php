<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    public const STATUS_PENDING = 'pending_verification';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NEEDS_REVISION = 'needs_revision';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'school_id', 'asset_acquisition_id', 'asset_type_id', 'submitted_by',
        'asset_code', 'name', 'brand_model', 'serial_number', 'location',
        'condition', 'year_acquired', 'notes', 'status', 'reviewed_by',
        'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return ['year_acquired' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(AssetAcquisition::class, 'asset_acquisition_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(AssetChangeRequest::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetEvent::class);
    }
}