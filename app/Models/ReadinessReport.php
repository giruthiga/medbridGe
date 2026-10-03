<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadinessReport extends Model
{
    protected $fillable = [
        'upload_id',
        'score',
        'breakdown',
        'details',
        'summary',
    ];

    protected $casts = [
        'breakdown' => 'array',
        'details' => 'array',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}