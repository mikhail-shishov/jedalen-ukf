<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Canteen;

class Article extends Model
{
    protected $fillable = [
        'title_sk', 'title_en', 'title_ua', 'title_ru',
        'content_sk', 'content_en', 'content_ua', 'content_ru',
        'image_path', 'is_published', 'users_id', 'canteens_id'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function canteen(): BelongsTo {
        return $this->belongsTo(Canteen::class, 'canteens_id');
    }
}