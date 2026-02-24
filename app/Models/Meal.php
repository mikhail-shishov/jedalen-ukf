<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_name',
        'name_sk',
        'name_en',
        'name_ua',
        'name_ru',
        'allergens',
        'image_path'
    ];

    public function orders() {
        return $this->hasMany(Order::class);
    }
}