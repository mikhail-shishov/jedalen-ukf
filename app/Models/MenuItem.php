<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['canteen_id', 'meal_id', 'date', 'stock_total', 'stock_current'];
}
