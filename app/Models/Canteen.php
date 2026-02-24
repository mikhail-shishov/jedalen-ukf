<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canteen extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'address'];

    public function articles(): HasMany {
        return $this->hasMany(Article::class, 'canteens_id');
    }
}
