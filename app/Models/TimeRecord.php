<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeRecord extends Model
{
    /** @use HasFactory<\Database\Factories\TimeRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
