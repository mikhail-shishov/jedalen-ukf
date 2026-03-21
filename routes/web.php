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
use Illuminate\Support\Facades\Schema;

// CSRF cookie route for SPA session initialization
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

    if (Auth::attempt(['login_id' => $credentials['login_id'], 'password' => $credentials['password']], false)) {
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
        return response()->json([
            'ok' => false,
            'message' => 'Nesprávne prihlasovacie údaje.',
        ], 401);
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

    // Protect payment SPA page from unauthenticated access.
    Route::get('/payment', function () {
        return view('app');
    });
});

// Session-based API routes (session middleware applied by default on web routes)
Route::middleware('auth')->get('/api/user', function (Request $request) {
    return response()->json($request->user());
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

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
