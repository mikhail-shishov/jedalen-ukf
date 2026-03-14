<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminMealController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminCanteenController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminMenuController;
use App\Http\Controllers\Admin\AdminImportController;
use App\Http\Controllers\Admin\GeminiController;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $today = now()->toDateString();
        $nextThreeDays = now()->addDays(2)->toDateString();

        $hasOrdersTable = Schema::hasTable('orders');
        $hasMenuItemsTable = Schema::hasTable('menu_items');

        $priceColumn = 'price';
        $hasMenuItemForeignKey = false;

        if ($hasOrdersTable) {
            $priceColumn = Schema::hasColumn('orders', 'price_paid') ? 'price_paid' : 'price';
            $hasMenuItemForeignKey = Schema::hasColumn('orders', 'menu_item_id');
        }

        $ordersToday = 0;
        $ordersNextThreeDays = 0;
        $inExchangeCount = 0;
        $menuItemsNextThreeDays = 0;
        $upcomingByDayAndCanteen = collect();
        $recentOrders = collect();

        if ($hasOrdersTable && $hasMenuItemsTable && $hasMenuItemForeignKey) {
            $ordersToday = DB::table('orders')
                ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
                ->whereDate('menu_items.date', $today)
                ->count();

            $ordersNextThreeDays = DB::table('orders')
                ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
                ->whereBetween('menu_items.date', [$today, $nextThreeDays])
                ->count();

            $menuItemsNextThreeDays = DB::table('menu_items')
                ->whereBetween('date', [$today, $nextThreeDays])
                ->count();

            $inExchangeCount = DB::table('orders')
                ->where('status', 'in_exchange')
                ->count();

            $upcomingByDayAndCanteen = DB::table('orders')
                ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
                ->leftJoin('canteens', 'menu_items.canteen_id', '=', 'canteens.id')
                ->whereBetween('menu_items.date', [$today, now()->addDays(6)->toDateString()])
                ->selectRaw('menu_items.date as order_date')
                ->selectRaw('COALESCE(canteens.name, "-") as canteen_name')
                ->selectRaw('COUNT(*) as orders_count')
                ->selectRaw('COALESCE(SUM(orders.' . $priceColumn . '), 0) as total_amount')
                ->groupBy('menu_items.date', 'canteens.name')
                ->orderBy('menu_items.date')
                ->orderBy('canteens.name')
                ->limit(14)
                ->get();

            $recentOrders = DB::table('orders')
                ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
                ->leftJoin('meals', 'menu_items.meal_id', '=', 'meals.id')
                ->leftJoin('canteens', 'menu_items.canteen_id', '=', 'canteens.id')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->selectRaw('orders.id')
                ->selectRaw('orders.status')
                ->selectRaw('orders.created_at')
                ->selectRaw('orders.' . $priceColumn . ' as price')
                ->selectRaw('menu_items.date as order_date')
                ->selectRaw('COALESCE(canteens.name, "-") as canteen_name')
                ->selectRaw('COALESCE(meals.name_sk, "-") as meal_name')
                ->selectRaw('COALESCE(users.first_name, "") as first_name')
                ->selectRaw('COALESCE(users.last_name, "") as last_name')
                ->orderByDesc('orders.created_at')
                ->limit(8)
                ->get();
        } elseif ($hasOrdersTable) {
            $ordersToday = DB::table('orders')
                ->whereDate('created_at', $today)
                ->count();

            $ordersNextThreeDays = DB::table('orders')
                ->whereBetween('created_at', [now()->startOfDay(), now()->addDays(2)->endOfDay()])
                ->count();

            $inExchangeCount = DB::table('orders')
                ->where('status', 'in_exchange')
                ->count();

            if ($hasMenuItemsTable) {
                $menuItemsNextThreeDays = DB::table('menu_items')
                    ->whereBetween('date', [$today, $nextThreeDays])
                    ->count();
            }

            $recentOrders = DB::table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->selectRaw('orders.id')
                ->selectRaw('orders.status')
                ->selectRaw('orders.created_at')
                ->selectRaw('orders.' . $priceColumn . ' as price')
                ->selectRaw('COALESCE(users.first_name, "") as first_name')
                ->selectRaw('COALESCE(users.last_name, "") as last_name')
                ->orderByDesc('orders.created_at')
                ->limit(8)
                ->get();
        }

        return view('admin.index', [
            'ordersToday' => $ordersToday,
            'ordersNextThreeDays' => $ordersNextThreeDays,
            'inExchangeCount' => $inExchangeCount,
            'menuItemsNextThreeDays' => $menuItemsNextThreeDays,
            'upcomingByDayAndCanteen' => $upcomingByDayAndCanteen,
            'recentOrders' => $recentOrders,
        ]);
    })->name('admin.dashboard');

    Route::get('/meals', [AdminMealController::class, 'index'])->name('admin.meals');
    Route::post('/meals', [AdminMealController::class, 'store'])->name('admin.meals.store');
    Route::put('/meals/{id}', [AdminMealController::class, 'update'])->name('admin.meals.update');
    Route::delete('/meals/{id}', [AdminMealController::class, 'destroy'])->name('admin.meals.destroy');
    Route::post('/meals/{id}/generate-image', [AdminMealController::class, 'generateImage'])->name('admin.meals.generate-image');
    Route::post('/meals/suggest-allergens', [AdminMealController::class, 'suggestAllergens'])->name('admin.meals.suggest-allergens');
    Route::post('/meals/suggest-translations', [AdminMealController::class, 'suggestTranslations'])->name('admin.meals.suggest-translations');

    Route::get('/menu', [AdminMenuController::class, 'index'])->name('admin.menu');
    Route::post('/menu', [AdminMenuController::class, 'store'])->name('admin.menu.store');
    Route::delete('/menu/{id}', [AdminMenuController::class, 'destroy'])->name('admin.menu.destroy');
    Route::get('/menu/meals/search', [AdminMenuController::class, 'search'])->name('admin.menu.search');

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
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');

    Route::get('/import', [AdminImportController::class, 'index'])->name('admin.import');
    Route::post('/import/preview', [AdminImportController::class, 'preview'])->name('admin.import.preview');
    Route::post('/import/store', [AdminImportController::class, 'store'])->name('admin.import.store');
    Route::post('/import/enrich', [AdminImportController::class, 'enrich'])->name('admin.import.enrich');

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
