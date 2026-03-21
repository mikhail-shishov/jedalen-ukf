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
use App\Http\Controllers\Admin\AdminCookController;
use App\Http\Controllers\Admin\GeminiController;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
})->middleware('web');

Route::get('/auth/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/auth/login', function (Request $request) {
    $credentials = $request->validate([
        'login_id' => ['required'],
        'password' => ['required'],
    ]);

    $throttleKey = Str::lower((string) ($credentials['login_id'] ?? '')) . '|' . $request->ip();
    $maxAttempts = 5;
    $decaySeconds = 60;

    if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $message = "Príliš veľa pokusov o prihlásenie. Skúste znovu o {$seconds} sekúnd.";

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 429);
        }

        return back()->withErrors([
            'login_id' => $message,
        ])->withInput($request->except('password'));
    }

    if (Auth::attempt(['login_id' => $credentials['login_id'], 'password' => $credentials['password']], false)) {
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'user' => Auth::user(),
            ]);
        }

        return redirect()->intended('admin');
    }

    if ($request->expectsJson()) {
        RateLimiter::hit($throttleKey, $decaySeconds);
        return response()->json([
            'ok' => false,
            'message' => 'Nesprávne prihlasovacie údaje.',
        ], 401);
    }

    RateLimiter::hit($throttleKey, $decaySeconds);

    return back()->withErrors([
        'login_id' => 'Nesprávne prihlasovacie údaje.',
    ]);
})->name('login.post');

Route::post('/auth/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/auth/login');
})->name('logout');

Route::get('/auth/logout', function () {
    return redirect('/auth/login');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::middleware('admin_or_cook')->group(function () {
        Route::get('/cook', [AdminCookController::class, 'index'])->name('admin.cook');
        Route::post('/cook/ingredients', [AdminCookController::class, 'storeIngredient'])->name('admin.cook.ingredients.store');
        Route::put('/cook/ingredients/{id}', [AdminCookController::class, 'updateIngredient'])->name('admin.cook.ingredients.update');
        Route::post('/cook/meal-ingredients', [AdminCookController::class, 'upsertMealIngredient'])->name('admin.cook.meal-ingredients.upsert');
    });

    Route::middleware('admin')->group(function () {
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
    Route::post('/menu/duplicate', [AdminMenuController::class, 'duplicate'])->name('admin.menu.duplicate');
    Route::get('/menu/days', [AdminMenuController::class, 'getDays'])->name('admin.menu.days');

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
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');

    Route::get('/import', [AdminImportController::class, 'index'])->name('admin.import');
    Route::post('/import/preview', [AdminImportController::class, 'preview'])->name('admin.import.preview');
    Route::post('/import/store', [AdminImportController::class, 'store'])->name('admin.import.store');
    Route::post('/import/enrich', [AdminImportController::class, 'enrich'])->name('admin.import.enrich');

    Route::post('/translate', [GeminiController::class, 'translate'])->name('admin.translate');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/{fallbackPlaceholder}', function () {
        abort(404);
    })->where('fallbackPlaceholder', '.*');

    Route::get('/payment', function () {
        return view('app');
    });

    Route::get('/payment/thank-you', function () {
        return view('app');
    });

    Route::get('/history', function () {
        return view('app');
    });

    Route::get('/settings', function () {
        return view('app');
    });
});

Route::middleware('auth')->get('/api/user', function (Request $request) {
    return response()->json($request->user());
});

Route::middleware('auth')->get('/api/settings/allergens', function () {
    $allergens = \App\Models\Allergen::query()
        ->orderByRaw('CAST(number AS UNSIGNED) ASC')
        ->orderBy('number')
        ->get(['id', 'number', 'name']);

    return response()->json($allergens);
});

Route::middleware('auth')->get('/api/settings/preferences', function (Request $request) {
    $user = $request->user();

    return response()->json([
        'blocked_allergens' => collect($user->blocked_allergen_numbers ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value >= 0)
            ->values()
            ->all(),
        'push_enabled' => (bool) $user->push_enabled,
        'push_locale' => in_array($user->push_locale, ['sk', 'en', 'ua', 'ru'], true) ? $user->push_locale : 'sk',
    ]);
});

Route::middleware('auth')->post('/api/settings/preferences', function (Request $request) {
    $validated = $request->validate([
        'blocked_allergens' => ['nullable', 'array'],
        'blocked_allergens.*' => ['integer', 'min:0'],
        'push_enabled' => ['nullable', 'boolean'],
        'push_locale' => ['nullable', 'string', 'in:sk,en,ua,ru'],
    ]);

    $user = $request->user();
    $user->blocked_allergen_numbers = array_values(array_unique(array_map(
        'intval',
        $validated['blocked_allergens'] ?? []
    )));

    if (array_key_exists('push_enabled', $validated)) {
        $user->push_enabled = (bool) $validated['push_enabled'];
    }

    if (!empty($validated['push_locale'])) {
        $user->push_locale = $validated['push_locale'];
    }

    $user->save();

    return response()->json([
        'ok' => true,
        'preferences' => [
            'blocked_allergens' => collect($user->blocked_allergen_numbers ?? [])->map(fn ($value) => (int) $value)->values()->all(),
            'push_enabled' => (bool) $user->push_enabled,
            'push_locale' => in_array($user->push_locale, ['sk', 'en', 'ua', 'ru'], true) ? $user->push_locale : 'sk',
        ],
    ]);
});

Route::middleware('auth')->get('/api/orders/active', function (Request $request) {
    if (!Schema::hasTable('orders')) {
        return response()->json(['items' => []]);
    }

    $userId = (int) $request->user()->id;
    $canteenId = (int) $request->query('canteen_id', 0);

    $hasOrdersUserId = Schema::hasColumn('orders', 'user_id');
    $hasOrdersMenuItemId = Schema::hasColumn('orders', 'menu_item_id');
    $hasOrdersStatus = Schema::hasColumn('orders', 'status');
    $hasMenuItemsTable = Schema::hasTable('menu_items');

    if (!$hasOrdersMenuItemId || !$hasMenuItemsTable) {
        return response()->json(['items' => []]);
    }

    $query = DB::table('orders')
        ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
        ->whereDate('menu_items.date', '>=', now()->toDateString())
        ->selectRaw('orders.id as id')
        ->selectRaw('orders.menu_item_id as menu_item_id');

    if ($hasOrdersUserId) {
        $query->where('orders.user_id', $userId);
    }

    if ($hasOrdersStatus) {
        $query->whereIn('orders.status', ['ordered', 'in_exchange']);
    }

    if ($canteenId > 0) {
        $query->where('menu_items.canteen_id', $canteenId);
    }

    $items = $query
        ->orderByDesc('orders.id')
        ->get()
        ->map(fn ($row) => [
            'id' => (int) $row->id,
            'menu_item_id' => (int) $row->menu_item_id,
        ])
        ->values();

    return response()->json(['items' => $items]);
});

Route::middleware('auth')->post('/api/orders', function (Request $request) {
    $validated = $request->validate([
        'menu_item_id' => ['required', 'integer', 'min:1'],
    ]);

    if (!Schema::hasTable('orders') || !Schema::hasTable('menu_items')) {
        return response()->json(['message' => 'Objednávky nie sú momentálne dostupné.'], 503);
    }

    $menuItemId = (int) $validated['menu_item_id'];
    $userId = (int) $request->user()->id;

    $menuItem = DB::table('menu_items')
        ->where('id', $menuItemId)
        ->select('id', 'meal_id', 'date')
        ->first();

    if (!$menuItem) {
        return response()->json(['message' => 'Jedlo v menu sa nenašlo.'], 404);
    }

    if (isset($menuItem->date) && (string) $menuItem->date < now()->toDateString()) {
        return response()->json(['message' => 'Objednávka pre minulý deň nie je povolená.'], 422);
    }

    $mealPrice = 0.0;
    if (isset($menuItem->meal_id) && Schema::hasTable('meals')) {
        $priceFromMeal = DB::table('meals')->where('id', (int) $menuItem->meal_id)->value('price');
        if ($priceFromMeal !== null) {
            $mealPrice = (float) $priceFromMeal;
        }
    }

    $hasOrdersUserId = Schema::hasColumn('orders', 'user_id');
    $hasOrdersMenuItemId = Schema::hasColumn('orders', 'menu_item_id');
    $hasOrdersStatus = Schema::hasColumn('orders', 'status');
    $hasUsersTable = Schema::hasTable('users');
    $hasUsersCreditBalance = $hasUsersTable && Schema::hasColumn('users', 'credit_balance');

    $hasPaymentsTable = Schema::hasTable('payments');
    $canWritePayments = $hasPaymentsTable
        && Schema::hasColumn('payments', 'user_id')
        && Schema::hasColumn('payments', 'status_id')
        && Schema::hasColumn('payments', 'method_id')
        && Schema::hasColumn('payments', 'amount')
        && Schema::hasColumn('payments', 'balance_before')
        && Schema::hasColumn('payments', 'balance_after')
        && Schema::hasColumn('payments', 'external_transaction_id');

    if (!$hasOrdersMenuItemId) {
        return response()->json(['message' => 'Objednávky nie sú kompatibilné s aktuálnou schémou.'], 500);
    }

    $result = DB::transaction(function () use (
        $menuItemId,
        $userId,
        $mealPrice,
        $menuItem,
        $hasOrdersUserId,
        $hasOrdersStatus,
        $hasUsersCreditBalance,
        $canWritePayments
    ) {
        $duplicateQuery = DB::table('orders')->where('menu_item_id', $menuItemId);
        if ($hasOrdersUserId) {
            $duplicateQuery->where('user_id', $userId);
        }
        if ($hasOrdersStatus) {
            $duplicateQuery->whereIn('status', ['ordered', 'in_exchange']);
        }

        $existingOrder = $duplicateQuery->select('id')->first();
        if ($existingOrder) {
            return [
                'type' => 'duplicate',
                'order_id' => (int) $existingOrder->id,
            ];
        }

        $balanceBefore = null;
        $balanceAfter = null;

        if ($hasUsersCreditBalance && $mealPrice > 0) {
            $userRow = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->select('id', 'credit_balance')
                ->first();

            $balanceBefore = (float) ($userRow->credit_balance ?? 0);
            if ($balanceBefore < $mealPrice) {
                return [
                    'type' => 'insufficient_balance',
                    'balance' => $balanceBefore,
                    'required' => $mealPrice,
                ];
            }

            $balanceAfter = round($balanceBefore - $mealPrice, 2);
            DB::table('users')
                ->where('id', $userId)
                ->update(['credit_balance' => $balanceAfter]);
        }

        $payload = [
            'menu_item_id' => $menuItemId,
        ];

        if ($hasOrdersUserId) {
            $payload['user_id'] = $userId;
        }
        if ($hasOrdersStatus) {
            $payload['status'] = 'ordered';
        }
        if (Schema::hasColumn('orders', 'meal_id') && isset($menuItem->meal_id)) {
            $payload['meal_id'] = (int) $menuItem->meal_id;
        }
        if (Schema::hasColumn('orders', 'price_paid')) {
            $payload['price_paid'] = $mealPrice;
        }
        if (Schema::hasColumn('orders', 'price')) {
            $payload['price'] = $mealPrice;
        }
        if (Schema::hasColumn('orders', 'created_at')) {
            $payload['created_at'] = now();
        }
        if (Schema::hasColumn('orders', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $orderId = (int) DB::table('orders')->insertGetId($payload);

        if ($canWritePayments && $balanceBefore !== null && $balanceAfter !== null) {
            $statusId = Schema::hasTable('payment_statuses') ? DB::table('payment_statuses')->orderBy('id')->value('id') : null;
            $methodId = Schema::hasTable('payment_methods') ? DB::table('payment_methods')->orderBy('id')->value('id') : null;

            if ($statusId !== null && $methodId !== null) {
                DB::table('payments')->insert([
                    'user_id' => $userId,
                    'status_id' => (int) $statusId,
                    'method_id' => (int) $methodId,
                    'amount' => -1 * $mealPrice,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'external_transaction_id' => 'ORDER_' . $orderId,
                    'error_message' => 'Úhrada objednávky jedla.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return [
            'type' => 'created',
            'order_id' => $orderId,
            'balance_after' => $balanceAfter,
        ];
    });

    if ($result['type'] === 'duplicate') {
        return response()->json([
            'ok' => true,
            'already_exists' => true,
            'order' => [
                'id' => (int) $result['order_id'],
                'menu_item_id' => $menuItemId,
            ],
        ]);
    }

    if ($result['type'] === 'insufficient_balance') {
        return response()->json([
            'message' => 'Nedostatočný kredit na účte.',
            'balance' => (float) $result['balance'],
            'required' => (float) $result['required'],
        ], 422);
    }

    return response()->json([
        'ok' => true,
        'order' => [
            'id' => (int) $result['order_id'],
            'menu_item_id' => $menuItemId,
        ],
        'balance_after' => $result['balance_after'],
    ]);
});

Route::middleware('auth')->delete('/api/orders/{orderId}', function (Request $request, int $orderId) {
    if (!Schema::hasTable('orders')) {
        return response()->json(['message' => 'Objednávky nie sú momentálne dostupné.'], 503);
    }

    $userId = (int) $request->user()->id;
    $hasOrdersUserId = Schema::hasColumn('orders', 'user_id');
    $hasOrdersStatus = Schema::hasColumn('orders', 'status');
    $hasOrdersMenuItemId = Schema::hasColumn('orders', 'menu_item_id');
    $hasMenuItemsTable = Schema::hasTable('menu_items');
    $hasExchangeTable = Schema::hasTable('exchange');
    $hasUsersCreditBalance = Schema::hasTable('users') && Schema::hasColumn('users', 'credit_balance');
    $canUseExchange = $hasMenuItemsTable && $hasExchangeTable && $hasOrdersMenuItemId;

    $hasPaymentsTable = Schema::hasTable('payments');
    $canWritePayments = $hasPaymentsTable
        && Schema::hasColumn('payments', 'user_id')
        && Schema::hasColumn('payments', 'status_id')
        && Schema::hasColumn('payments', 'method_id')
        && Schema::hasColumn('payments', 'amount')
        && Schema::hasColumn('payments', 'balance_before')
        && Schema::hasColumn('payments', 'balance_after')
        && Schema::hasColumn('payments', 'external_transaction_id');

    $result = DB::transaction(function () use (
        $orderId,
        $userId,
        $hasOrdersUserId,
        $hasOrdersStatus,
        $hasUsersCreditBalance,
        $canWritePayments,
        $canUseExchange,
        $hasMenuItemsTable,
        $hasExchangeTable
    ) {
        $query = DB::table('orders')->where('id', $orderId)->lockForUpdate();
        if ($hasOrdersUserId) {
            $query->where('user_id', $userId);
        }

        $order = $query->first();
        if (!$order) {
            return ['type' => 'not_found'];
        }

        if ($hasOrdersStatus && isset($order->status) && $order->status === 'cancelled') {
            return ['type' => 'already_cancelled'];
        }

        $amountPaid = 0.0;
        if (isset($order->price_paid) && $order->price_paid !== null) {
            $amountPaid = (float) $order->price_paid;
        } elseif (isset($order->price) && $order->price !== null) {
            $amountPaid = (float) $order->price;
        }

        $shouldGoToExchange = false;
        if ($canUseExchange && isset($order->menu_item_id) && $order->menu_item_id) {
            $menuItem = DB::table('menu_items')
                ->where('id', $order->menu_item_id)
                ->select('date')
                ->lockForUpdate()
                ->first();

            if ($menuItem && isset($menuItem->date)) {
                $serveDate = \Carbon\Carbon::createFromFormat('Y-m-d', $menuItem->date)->startOfDay();
                $deadline = $serveDate->copy()->subDay()->setHour(14)->setMinute(0);
                $now = now();

                if ($now > $deadline && $now < $serveDate) {
                    $shouldGoToExchange = true;

                    DB::table('exchange')->insert([
                        'order_id' => $orderId,
                        'seller_id' => $userId,
                        'buyer_id' => null,
                        'listing_price' => $amountPaid,
                        'status' => 'active',
                    ]);
                }
            }
        }

        if ($hasOrdersStatus) {
            DB::table('orders')
                ->where('id', $orderId)
                ->update(['status' => 'cancelled']);
        } else {
            DB::table('orders')
                ->where('id', $orderId)
                ->delete();
        }

        if (!$shouldGoToExchange && $hasUsersCreditBalance && $amountPaid > 0) {
            $userRow = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->select('id', 'credit_balance')
                ->first();

            $balanceBefore = (float) ($userRow->credit_balance ?? 0);
            $balanceAfter = round($balanceBefore + $amountPaid, 2);

            DB::table('users')
                ->where('id', $userId)
                ->update(['credit_balance' => $balanceAfter]);

            if ($canWritePayments) {
                $statusId = Schema::hasTable('payment_statuses') ? DB::table('payment_statuses')->orderBy('id')->value('id') : null;
                $methodId = Schema::hasTable('payment_methods') ? DB::table('payment_methods')->orderBy('id')->value('id') : null;

                if ($statusId !== null && $methodId !== null) {
                    DB::table('payments')->insert([
                        'user_id' => $userId,
                        'status_id' => (int) $statusId,
                        'method_id' => (int) $methodId,
                        'amount' => $amountPaid,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'external_transaction_id' => 'ORDER_CANCEL_' . $orderId,
                        'error_message' => 'Vrátenie platby po zrušení objednávky.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return [
                'type' => 'cancelled_refunded',
                'balance_after' => $balanceAfter,
            ];
        }

        if ($shouldGoToExchange) {
            return [
                'type' => 'sent_to_exchange',
            ];
        }

        return ['type' => 'cancelled'];
    });

    if ($result['type'] === 'not_found') {
        return response()->json(['message' => 'Objednávka sa nenašla.'], 404);
    }

    if ($result['type'] === 'sent_to_exchange') {
        return response()->json([
            'ok' => true,
            'message' => 'Objednávka bola umiestnená na burzu.',
        ]);
    }

    return response()->json([
        'ok' => true,
        'balance_after' => $result['balance_after'] ?? null,
    ]);
});

Route::middleware('auth')->get('/api/exchange', function (Request $request) {
    $canteenId = (int) $request->query('canteen_id', 0);

    if (!Schema::hasTable('exchange') || !Schema::hasTable('orders') || !Schema::hasTable('menu_items') || !Schema::hasTable('meals')) {
        return response()->json(['items' => []]);
    }

    $hasMenuItemCanteenId = Schema::hasColumn('menu_items', 'canteen_id');

    if (!$canteenId || !$hasMenuItemCanteenId) {
        return response()->json(['items' => []]);
    }

    try {
        $exchangeListings = DB::table('exchange')
            ->where('exchange.status', 'active')
            ->join('orders', 'exchange.order_id', '=', 'orders.id')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->where('menu_items.canteen_id', $canteenId)
            ->whereDate('menu_items.date', '>=', now()->toDateString())
            ->leftJoin('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->select(
                'exchange.id as exchange_id',
                'exchange.order_id',
                'exchange.seller_id',
                'exchange.listing_price',
                'orders.menu_item_id',
                'menu_items.date',
                DB::raw('COALESCE(meals.name_sk, "Jedlo") as name_sk'),
                DB::raw('COALESCE(meals.name_en, "Meal") as name_en'),
                DB::raw('COALESCE(meals.name_ua, "Їдо") as name_ua'),
                DB::raw('COALESCE(meals.name_ru, "Блюдо") as name_ru'),
                'meals.price',
                'meals.image_path',
                DB::raw('COALESCE(meals.badge, "") as badge')
            )
            ->orderBy('menu_items.date')
            ->get();

        $items = $exchangeListings->map(function ($listing) {
            return [
                'id' => $listing->exchange_id,
                'order_id' => $listing->order_id,
                'seller_id' => $listing->seller_id,
                'menu_item_id' => $listing->menu_item_id,
                'date' => $listing->date,
                'name_sk' => $listing->name_sk ?? 'Jedlo',
                'name_en' => $listing->name_en ?? 'Meal',
                'name_ua' => $listing->name_ua ?? 'Їдо',
                'name_ru' => $listing->name_ru ?? 'Блюдо',
                'price' => number_format($listing->listing_price, 2, '.', ''),
                'image_url' => $listing->image_path ? asset('storage/' . $listing->image_path) : null,
                'badge' => $listing->badge ?? '',
            ];
        })->values()->all();

        return response()->json(['items' => $items]);
    } catch (\Exception $e) {
        Log::error('Exchange API error: ' . $e->getMessage());
        return response()->json(['items' => []]);
    }
});

Route::middleware('auth')->post('/api/exchange/{exchangeId}/purchase', function (Request $request, int $exchangeId) {
    if (!Schema::hasTable('exchange') || !Schema::hasTable('orders')) {
        return response()->json(['message' => 'Biržu nie sú momentálne dostupné.'], 503);
    }

    $buyerId = (int) $request->user()->id;

    $result = DB::transaction(function () use ($exchangeId, $buyerId) {
        $exchange = DB::table('exchange')
            ->where('id', $exchangeId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (!$exchange) {
            return ['type' => 'not_found'];
        }

        $order = DB::table('orders')
            ->where('id', $exchange->order_id)
            ->select('id', 'menu_item_id', 'price_paid')
            ->lockForUpdate()
            ->first();

        if (!$order) {
            return ['type' => 'order_not_found'];
        }

        $purchaseAmount = (float) $exchange->listing_price;
        $balanceAfter = null;

        if (Schema::hasColumn('users', 'credit_balance')) {
            $buyerRow = DB::table('users')
                ->where('id', $buyerId)
                ->select('id', 'credit_balance')
                ->lockForUpdate()
                ->first();

            $buyerBalance = (float) ($buyerRow->credit_balance ?? 0);
            if ($buyerBalance < $purchaseAmount) {
                return ['type' => 'insufficient_balance'];
            }

            $balanceBefore = $buyerBalance;
            $balanceAfter = round($balanceBefore - $purchaseAmount, 2);
            DB::table('users')
                ->where('id', $buyerId)
                ->update(['credit_balance' => $balanceAfter]);

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'credit_balance')) {
                $sellerRow = DB::table('users')
                    ->where('id', $exchange->seller_id)
                    ->select('id', 'credit_balance')
                    ->lockForUpdate()
                    ->first();

                if ($sellerRow) {
                    $sellerBalance = (float) ($sellerRow->credit_balance ?? 0);
                    $sellerNewBalance = round($sellerBalance + $purchaseAmount, 2);
                    DB::table('users')
                        ->where('id', $exchange->seller_id)
                        ->update(['credit_balance' => $sellerNewBalance]);
                }
            }
        }

        $newOrderId = DB::table('orders')->insertGetId([
            'user_id' => $buyerId,
            'menu_item_id' => $order->menu_item_id,
            'price_paid' => $purchaseAmount,
            'status' => 'ordered',
            'created_at' => now(),
        ]);

        DB::table('exchange')
            ->where('id', $exchangeId)
            ->update([
                'buyer_id' => $buyerId,
                'status' => 'sold',
            ]);

        return [
            'type' => 'purchased',
            'new_order_id' => $newOrderId,
            'balance_after' => $balanceAfter,
        ];
    });

    if ($result['type'] === 'not_found') {
        return response()->json(['message' => 'Ponuka na burze sa nenašla.'], 404);
    }

    if ($result['type'] === 'insufficient_balance') {
        return response()->json([
            'message' => 'Nedostatočný kredit na účte.',
            'insufficient_balance' => true,
        ], 422);
    }

    return response()->json([
        'ok' => true,
        'order_id' => $result['new_order_id'],
        'balance_after' => $result['balance_after'],
    ]);
});

Route::middleware('auth')->get('/api/statistics', function (Request $request) {
    $userId = (int) $request->user()->id;

    $payload = [
        'most_ordered_meal' => null,
        'peak_visit_day' => null,
        'total_visits' => null,
    ];

    if (!Schema::hasTable('orders')) {
        return response()->json($payload);
    }

    $hasMenuItemsTable = Schema::hasTable('menu_items');
    $hasMealsTable = Schema::hasTable('meals');
    $hasOrdersMenuItemId = Schema::hasColumn('orders', 'menu_item_id');
    $hasOrdersMealId = Schema::hasColumn('orders', 'meal_id');
    $hasOrdersCreatedAt = Schema::hasColumn('orders', 'created_at');
    $hasMenuItemsDate = $hasMenuItemsTable && Schema::hasColumn('menu_items', 'date');

    if ($hasMealsTable && $hasMenuItemsTable && $hasOrdersMenuItemId && Schema::hasColumn('menu_items', 'meal_id')) {
        $topMeal = DB::table('orders')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->join('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->where('orders.user_id', $userId)
            ->groupBy('menu_items.meal_id', 'meals.name_sk', 'meals.name_en', 'meals.name_ua', 'meals.name_ru')
            ->selectRaw('menu_items.meal_id as meal_id')
            ->selectRaw('COUNT(*) as user_orders_count')
            ->selectRaw('COALESCE(meals.name_sk, "-") as name_sk')
            ->selectRaw('COALESCE(meals.name_en, meals.name_sk, "-") as name_en')
            ->selectRaw('COALESCE(meals.name_ua, meals.name_sk, "-") as name_ua')
            ->selectRaw('COALESCE(meals.name_ru, meals.name_sk, "-") as name_ru')
            ->orderByDesc('user_orders_count')
            ->first();

        if ($topMeal) {
            $orderedByUsers = DB::table('orders')
                ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
                ->where('menu_items.meal_id', $topMeal->meal_id)
                ->distinct('orders.user_id')
                ->count('orders.user_id');

            $payload['most_ordered_meal'] = [
                'name_sk' => (string) $topMeal->name_sk,
                'name_en' => (string) $topMeal->name_en,
                'name_ua' => (string) $topMeal->name_ua,
                'name_ru' => (string) $topMeal->name_ru,
                'user_orders_count' => (int) $topMeal->user_orders_count,
                'ordered_by_users_count' => (int) $orderedByUsers,
            ];
        }
    } elseif ($hasMealsTable && $hasOrdersMealId) {
        $topMeal = DB::table('orders')
            ->join('meals', 'orders.meal_id', '=', 'meals.id')
            ->where('orders.user_id', $userId)
            ->groupBy('orders.meal_id', 'meals.name_sk', 'meals.name_en', 'meals.name_ua', 'meals.name_ru')
            ->selectRaw('orders.meal_id as meal_id')
            ->selectRaw('COUNT(*) as user_orders_count')
            ->selectRaw('COALESCE(meals.name_sk, "-") as name_sk')
            ->selectRaw('COALESCE(meals.name_en, meals.name_sk, "-") as name_en')
            ->selectRaw('COALESCE(meals.name_ua, meals.name_sk, "-") as name_ua')
            ->selectRaw('COALESCE(meals.name_ru, meals.name_sk, "-") as name_ru')
            ->orderByDesc('user_orders_count')
            ->first();

        if ($topMeal) {
            $orderedByUsers = DB::table('orders')
                ->where('meal_id', $topMeal->meal_id)
                ->distinct('user_id')
                ->count('user_id');

            $payload['most_ordered_meal'] = [
                'name_sk' => (string) $topMeal->name_sk,
                'name_en' => (string) $topMeal->name_en,
                'name_ua' => (string) $topMeal->name_ua,
                'name_ru' => (string) $topMeal->name_ru,
                'user_orders_count' => (int) $topMeal->user_orders_count,
                'ordered_by_users_count' => (int) $orderedByUsers,
            ];
        }
    }

    if ($hasMenuItemsTable && $hasOrdersMenuItemId && $hasMenuItemsDate) {
        $peakDay = DB::table('orders')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->where('orders.user_id', $userId)
            ->groupBy(DB::raw('DAYOFWEEK(menu_items.date)'))
            ->selectRaw('DAYOFWEEK(menu_items.date) as day_of_week')
            ->selectRaw('COUNT(*) as orders_count')
            ->orderByDesc('orders_count')
            ->first();

        $totalVisits = DB::table('orders')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->where('orders.user_id', $userId)
            ->distinct()
            ->count(DB::raw('DATE(menu_items.date)'));

        if ($peakDay) {
            $payload['peak_visit_day'] = [
                'day_of_week' => (int) $peakDay->day_of_week,
                'orders_count' => (int) $peakDay->orders_count,
            ];
        }

        if ($totalVisits > 0) {
            $payload['total_visits'] = [
                'count' => (int) $totalVisits,
            ];
        }
    } elseif ($hasOrdersCreatedAt) {
        $peakDay = DB::table('orders')
            ->where('user_id', $userId)
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
            ->selectRaw('DAYOFWEEK(created_at) as day_of_week')
            ->selectRaw('COUNT(*) as orders_count')
            ->orderByDesc('orders_count')
            ->first();

        $totalVisits = DB::table('orders')
            ->where('user_id', $userId)
            ->distinct()
            ->count(DB::raw('DATE(created_at)'));

        if ($peakDay) {
            $payload['peak_visit_day'] = [
                'day_of_week' => (int) $peakDay->day_of_week,
                'orders_count' => (int) $peakDay->orders_count,
            ];
        }

        if ($totalVisits > 0) {
            $payload['total_visits'] = [
                'count' => (int) $totalVisits,
            ];
        }
    }

    return response()->json($payload);
});

Route::middleware('auth')->post('/api/payments/create-intent', function (Request $request) {
    $validated = $request->validate([
        'amount' => ['required', 'integer', 'min:50', 'max:100000'],
        'currency' => ['nullable', 'string'],
    ]);

    $secretKey = config('services.stripe.secret_key');
    if (!$secretKey) {
        return response()->json([
            'message' => 'Stripe key is not configured.',
        ], 500);
    }

    $currency = strtolower($validated['currency'] ?? 'eur');

    \Illuminate\Support\Facades\Log::channel('payments')->info('Stripe create-intent requested', [
        'user_id' => optional($request->user())->id,
        'amount' => $validated['amount'],
        'currency' => $currency,
    ]);

    try {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = \Illuminate\Support\Facades\Http::asForm()
            ->withToken($secretKey)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $validated['amount'],
                'currency' => $currency,
                'automatic_payment_methods[enabled]' => 'true',
                'metadata[user_id]' => (string) $request->user()->id,
            ]);

        if ($response->failed()) {
            $stripeError = $response->json('error');

            \Illuminate\Support\Facades\Log::channel('payments')->error('Stripe create-intent failed', [
                'status' => $response->status(),
                'stripe_error' => $stripeError,
                'user_id' => optional($request->user())->id,
                'amount' => $validated['amount'],
                'currency' => $currency,
            ]);

            return response()->json([
                'message' => $stripeError['message'] ?? 'Payment intent creation failed.',
                'code' => $stripeError['code'] ?? null,
                'type' => $stripeError['type'] ?? null,
            ], 422);
        }

        \Illuminate\Support\Facades\Log::channel('payments')->info('Stripe create-intent succeeded', [
            'status' => $response->status(),
            'payment_intent_id' => $response->json('id'),
            'user_id' => optional($request->user())->id,
            'amount' => $validated['amount'],
            'currency' => $currency,
        ]);

        return response()->json([
            'client_secret' => $response->json('client_secret'),
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::channel('payments')->error('Stripe create-intent exception', [
            'message' => $e->getMessage(),
            'user_id' => optional($request->user())->id,
            'amount' => $validated['amount'],
            'currency' => $currency,
        ]);

        return response()->json([
            'message' => 'Payment service is temporarily unavailable.',
        ], 500);
    }
});

Route::middleware('auth')->post('/api/payments/confirm', function (Request $request) {
    $validated = $request->validate([
        'payment_intent_id' => ['required', 'string'],
    ]);

    $secretKey = config('services.stripe.secret_key');
    if (!$secretKey) {
        return response()->json([
            'message' => 'Stripe key is not configured.',
        ], 500);
    }

    $paymentIntentId = $validated['payment_intent_id'];
    $user = $request->user();

    $existingPayment = \App\Models\Payment::query()
        ->where('external_transaction_id', $paymentIntentId)
        ->where('user_id', $user->id)
        ->first();

    if ($existingPayment) {
        return response()->json([
            'ok' => true,
            'already_processed' => true,
            'new_balance' => (float) $user->fresh()->credit_balance,
        ]);
    }

    /** @var \Illuminate\Http\Client\Response $intentResponse */
    $intentResponse = \Illuminate\Support\Facades\Http::withToken($secretKey)
        ->get("https://api.stripe.com/v1/payment_intents/{$paymentIntentId}");

    if ($intentResponse->failed()) {
        \Illuminate\Support\Facades\Log::channel('payments')->error('Stripe confirm fetch failed', [
            'status' => $intentResponse->status(),
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $user->id,
            'body' => $intentResponse->json(),
        ]);

        return response()->json([
            'message' => 'Unable to verify payment status.',
        ], 422);
    }

    $intent = $intentResponse->json();
    $intentStatus = $intent['status'] ?? null;
    $intentUserId = (string) ($intent['metadata']['user_id'] ?? '');
    $amountReceived = (int) ($intent['amount_received'] ?? 0);
    $amount = (float) ($amountReceived / 100);

    if ($intentStatus !== 'succeeded') {
        return response()->json([
            'message' => 'Payment has not been completed yet.',
        ], 422);
    }

    if ($intentUserId !== (string) $user->id) {
        \Illuminate\Support\Facades\Log::channel('payments')->warning('Stripe confirm user mismatch', [
            'payment_intent_id' => $paymentIntentId,
            'request_user_id' => $user->id,
            'intent_user_id' => $intentUserId,
        ]);

        return response()->json([
            'message' => 'Payment does not belong to current user.',
        ], 403);
    }

    if ($amount <= 0) {
        return response()->json([
            'message' => 'Received payment amount is invalid.',
        ], 422);
    }

    $result = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $paymentIntentId) {
        $lockedUser = \App\Models\User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
        $balanceBefore = (float) $lockedUser->credit_balance;
        $balanceAfter = $balanceBefore + $amount;

        $payment = \App\Models\Payment::query()->create([
            'user_id' => $lockedUser->id,
            'status_id' => 1, // Completed
            'method_id' => 2, // Credit Card
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'external_transaction_id' => $paymentIntentId,
            'error_message' => null,
        ]);

        $lockedUser->credit_balance = $balanceAfter;
        $lockedUser->save();

        return [
            'payment_id' => $payment->id,
            'new_balance' => $balanceAfter,
        ];
    });

    \Illuminate\Support\Facades\Log::channel('payments')->info('Stripe payment credited', [
        'payment_intent_id' => $paymentIntentId,
        'user_id' => $user->id,
        'amount' => $amount,
        'payment_id' => $result['payment_id'],
        'new_balance' => $result['new_balance'],
    ]);

    return response()->json([
        'ok' => true,
        'new_balance' => $result['new_balance'],
    ]);
});

Route::middleware('auth')->get('/api/payments/history', function (Request $request) {
    $validated = $request->validate([
        'offset' => ['nullable', 'integer', 'min:0'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
    ]);

    $offset = (int) ($validated['offset'] ?? 0);
    $limit = (int) ($validated['limit'] ?? 10);

    $baseQuery = \App\Models\Payment::query()
        ->leftJoin('payment_statuses', 'payments.status_id', '=', 'payment_statuses.id')
        ->leftJoin('payment_methods', 'payments.method_id', '=', 'payment_methods.id')
        ->where('payments.user_id', $request->user()->id);

    $total = (clone $baseQuery)->count('payments.id');

    $items = (clone $baseQuery)
        ->orderByDesc('payments.created_at')
        ->offset($offset)
        ->limit($limit)
        ->get([
            'payments.id',
            'payments.created_at',
            'payments.amount',
            'payments.external_transaction_id',
            'payments.error_message',
            'payment_statuses.name as status_name',
            'payment_methods.name as method_name',
        ])
        ->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'created_at' => optional($row->created_at)->toISOString(),
                'amount' => (float) $row->amount,
                'status' => $row->status_name ?: 'Neznámy stav',
                'method' => $row->method_name ?: 'Neznáma metóda',
                'external_transaction_id' => $row->external_transaction_id,
                'note' => $row->error_message,
            ];
        });

    return response()->json([
        'total' => $total,
        'items' => $items,
    ]);
});

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
