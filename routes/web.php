<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminMealController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminArticleController;

Route::redirect('/login', '/auth/login');

Route::get('/auth/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/auth/login', function (Request $request) {
    $credentials = $request->validate([
        'login_id' => ['required'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->remember)) {
        $request->session()->regenerate();
        return redirect()->intended('admin');
    }

    return back()->withErrors([
        'login_id' => 'Nesprávne prihlasovacie údaje.',
    ]);
})->name('login.post');

Route::get('/auth/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/auth/login');
})->name('logout');

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    })->name('admin.dashboard');

    Route::get('/meals', [AdminMealController::class, 'index'])->name('admin.meals');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders');
    Route::get('/articles', [AdminArticleController::class, 'index'])->name('admin.articles');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/{fallbackPlaceholder}', function () {
        abort(404);
    })->where('fallbackPlaceholder', '.*');
});

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
