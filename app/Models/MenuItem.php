<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'canteen_id', 
        'meal_id', 
        'date', 
        'stock_total', 
        'stock_current'
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}