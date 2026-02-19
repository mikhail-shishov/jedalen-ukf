<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'meal'])->orderBy('created_at', 'desc')->get();
        
        return view('admin.orders', compact('orders'));
    }
}