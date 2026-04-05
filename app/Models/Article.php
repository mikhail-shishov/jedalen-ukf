<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Canteen;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title_sk',
        'title_en',
        'title_ua',
        'title_ru',
        'content_sk',
        'content_en',
        'content_ua',
        'content_ru',
        'image_path',
        'is_published',
        'users_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function canteens()
    {
        return $this->belongsToMany(
            Canteen::class,
            'articles_has_canteens',
            'articles_id',
            'canteens_id'
        );
    }

    public function revisions()
    {
        return $this->hasMany(ArticleRevision::class, 'article_id');
    }
}
