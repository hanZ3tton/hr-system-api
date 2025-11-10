<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'check_in_time',
        'check_out_time',
        'status',
        'latitude',
        'longitude'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
