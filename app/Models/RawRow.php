<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawRow extends Model
{
    protected $fillable = [
        'upload_id',
        'row_number',
        'data',
        'is_malformed',
        'field_count',
        'malformed_reason',
    ];

    protected $casts = [
        'data' => 'array',
        'is_malformed' => 'boolean',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}