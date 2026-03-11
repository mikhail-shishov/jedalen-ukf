<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'raw_name',
        'name_sk',
        'name_en',
        'name_ua',
        'name_ru',
        'image_path',
        'price'
    ];

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'meals_has_allergens', 'meals_id', 'allergens_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function canteens()
    {
        return $this->belongsToMany(Canteen::class, 'menu_items', 'meal_id', 'canteen_id')
            ->withPivot('date');
    }
}
