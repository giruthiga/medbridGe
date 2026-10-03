<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Upload extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'row_count',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rawRows(): HasMany
    {
        return $this->hasMany(RawRow::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchModel::class);
    }

    public function cleanedRecords(): HasMany
    {
        return $this->hasMany(CleanedRecord::class);
    }

    public function readinessReport(): HasOne
    {
        return $this->hasOne(ReadinessReport::class);
    }
}