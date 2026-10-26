<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    public const COORDINATE_MISSING = 'missing';
    public const COORDINATE_PENDING = 'pending';
    public const COORDINATE_VERIFIED = 'verified';
    public const COORDINATE_NEEDS_REVIEW = 'needs_review';
    public const COORDINATE_OUTSIDE_BOUNDARY = 'outside_boundary';

    protected $fillable = [
        'sekolah_id',
        'npsn',
        'nama',
        'bentuk_pendidikan',
        'status_sekolah',
        'alamat_jalan',
        'nama_dusun',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'nomor_telepon',
        'akreditasi',
        'lintang',
        'bujur',
        'coordinate_status',
        'coordinate_verified_at',
        'coordinate_verified_by',
    ];

    protected function casts(): array
    {
        return [
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
            'coordinate_verified_at' => 'datetime',
        ];
    }

    public function operators(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function coordinateVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinate_verified_by');
    }

    public function coordinateChangeRequests(): HasMany
    {
        return $this->hasMany(SchoolCoordinateChangeRequest::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetAcquisitions(): HasMany
    {
        return $this->hasMany(AssetAcquisition::class);
    }

    public function hasUsableCoordinates(): bool
    {
        return in_array($this->coordinate_status, [
            self::COORDINATE_PENDING,
            self::COORDINATE_VERIFIED,
        ], true)
            && $this->lintang !== null
            && $this->bujur !== null;
    }
}
