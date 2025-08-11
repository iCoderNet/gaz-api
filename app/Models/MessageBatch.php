<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageBatch extends Model
{
    protected $fillable = [
        'message',
        'stats',
        'status',
        'user_ids',
        'created_by'
    ];

    protected $casts = [
        'stats' => 'array',
        'user_ids' => 'array'
    ];

    // created_by maydoni uchun relation
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}