<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = ['code', 'name', 'scheme'];

    public function types(): HasMany
    {
        return $this->hasMany(AssetType::class, 'asset_category_id');
    }
}