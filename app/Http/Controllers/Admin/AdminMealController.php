<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use Illuminate\Http\Request;

class AdminMealController extends Controller
{
    public function index(){
        $meals = Meal::all();
        return view('admin.meals', compact('meals'));
    }

    public function enrich($id) {}
}
