<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in_at',
        'check_in_location',
        'check_in_ip',
        'check_out_at',
        'check_out_location',
        'check_out_ip',
        'status',
        'worked_second',
        'note',
        'check_in_photo_path',
        'check_out_photo_path',
        'check_in_photo_mime',
        'check_out_photo_mime',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getWorkedDurationAttribute()
    {
        if (!$this->worked_second) return null;
        return gmdate('H:i:s', $this->worked_second);
    }

    public function getCheckInPhotoUrlAttribute()
    {
        return $this->check_in_photo_path ? Storage::url($this->check_in_photo_path) : null;
    }

    public function getCheckOutPhotoUrlAttribute()
    {
        return $this->check_out_photo_path ? Storage::url($this->check_out_photo_path) : null;
    }
}
