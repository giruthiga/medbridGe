<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleanedRecord extends Model
{
    protected $fillable = [
        'upload_id',
        'row_number',
        'original_data',
        'cleaned_data',
        'changes',
        'issues_count',
    ];

    protected $casts = [
        'original_data' => 'array',
        'cleaned_data' => 'array',
        'changes' => 'array',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
}