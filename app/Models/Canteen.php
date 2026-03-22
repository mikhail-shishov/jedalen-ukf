<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canteen extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'address',
        'timezone',
        'notifications_enabled',
        'notify_open_offset_min',
        'notify_close_offset_min',
        'open_time_mon',
        'close_time_mon',
        'open_time_tue',
        'close_time_tue',
        'open_time_wed',
        'close_time_wed',
        'open_time_thu',
        'close_time_thu',
        'open_time_fri',
        'close_time_fri',
        'open_time_sat',
        'close_time_sat',
        'open_time_sun',
        'close_time_sun',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'notify_open_offset_min' => 'integer',
        'notify_close_offset_min' => 'integer',
    ];

    public function articles(): HasMany {
        return $this->hasMany(Article::class, 'canteens_id');
    }
}
