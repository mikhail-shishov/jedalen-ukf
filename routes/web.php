<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminMealController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminCanteenController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\GeminiController;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Storage;

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
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('admin.articles.create');
    Route::post('/articles/store', [AdminArticleController::class, 'store'])->name('admin.articles.store');
    Route::get('/articles/{id}/edit', [AdminArticleController::class, 'edit'])->name('admin.articles.edit');
    Route::put('/articles/{id}/update', [AdminArticleController::class, 'update'])->name('admin.articles.update');
    Route::delete('/articles/{id}', [AdminArticleController::class, 'destroy'])->name('admin.articles.destroy');

    Route::get('/canteens', [AdminCanteenController::class, 'index'])->name('admin.canteens');
    Route::post('/canteens', [AdminCanteenController::class, 'store'])->name('admin.canteens.store');
    Route::put('/canteens/{id}/edit', [AdminCanteenController::class, 'update'])->name('admin.canteens.edit');
    Route::put('/canteens/{id}', [AdminCanteenController::class, 'update'])->name('admin.canteens.update');
    Route::delete('/canteens/{id}', [AdminCanteenController::class, 'destroy'])->name('admin.canteens.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');

    Route::post('/translate', [GeminiController::class, 'translate'])->name('admin.translate');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/{fallbackPlaceholder}', function () {
        abort(404);
    })->where('fallbackPlaceholder', '.*');
});

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
