<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAcquisition extends Model
{
    public const STATUS_PENDING = 'pending_verification';
    public const STATUS_VERIFIED_BMD = 'verified_bmd';
    public const STATUS_VERIFIED_NON_BMD = 'verified_non_bmd';
    public const STATUS_NEEDS_REVISION = 'needs_revision';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'school_id', 'created_by', 'funding_source', 'acquisition_method', 'owner_entity',
        'provider_name', 'document_reference', 'acquired_at', 'total_value', 'notes',
        'ownership_status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'total_value' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AssetAcquisitionReview::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetEvent::class);
    }

    public function canReceiveAssets(): bool
    {
        return in_array($this->ownership_status, [
            self::STATUS_PENDING,
            self::STATUS_NEEDS_REVISION,
        ], true);
    }
}