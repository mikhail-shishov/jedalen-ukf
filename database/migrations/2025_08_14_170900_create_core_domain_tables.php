<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->increments('id');
                $table->enum('name', ['STUDENT', 'WORKER', 'COOK', 'ADMIN']);
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('login_id', 20)->unique();
                $table->string('password');
                $table->string('email', 100)->nullable();
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->decimal('credit_balance', 10, 2)->default(0);
                $table->boolean('is_first_login')->default(true);
                $table->unsignedInteger('role_id')->nullable();
                $table->rememberToken();
                $table->timestamps();

                $table->foreign('role_id', 'fk_users_roles')->references('id')->on('roles');
            });
        }

        if (!Schema::hasTable('allergens')) {
            Schema::create('allergens', function (Blueprint $table) {
                $table->increments('id');
                $table->string('number', 2)->nullable();
                $table->string('name')->nullable();
            });
        }

        if (!Schema::hasTable('canteens')) {
            Schema::create('canteens', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('address');
                $table->string('timezone', 64)->default('Europe/Bratislava');
                $table->boolean('notifications_enabled')->default(true);
                $table->time('open_time_mon')->nullable()->default('11:00:00');
                $table->time('close_time_mon')->nullable()->default('13:30:00');
                $table->time('open_time_tue')->nullable()->default('11:00:00');
                $table->time('close_time_tue')->nullable()->default('13:30:00');
                $table->time('open_time_wed')->nullable()->default('11:00:00');
                $table->time('close_time_wed')->nullable()->default('13:30:00');
                $table->time('open_time_thu')->nullable()->default('11:00:00');
                $table->time('close_time_thu')->nullable()->default('13:30:00');
                $table->time('open_time_fri')->nullable()->default('11:00:00');
                $table->time('close_time_fri')->nullable()->default('13:30:00');
                $table->time('open_time_sat')->nullable();
                $table->time('close_time_sat')->nullable();
                $table->time('open_time_sun')->nullable();
                $table->time('close_time_sun')->nullable();
                $table->smallInteger('notify_open_offset_min')->default(30);
                $table->smallInteger('notify_close_offset_min')->default(30);
            });
        }

        if (!Schema::hasTable('meals')) {
            Schema::create('meals', function (Blueprint $table) {
                $table->increments('id');
                $table->string('raw_name')->nullable();
                $table->string('name_sk')->nullable();
                $table->string('name_en')->nullable();
                $table->string('name_ua')->nullable();
                $table->string('name_ru')->nullable();
                $table->string('image_path')->nullable();
                $table->decimal('price', 10, 2)->nullable();
            });
        }

        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('canteen_id');
                $table->unsignedInteger('meal_id');
                $table->date('date');
                $table->integer('stock_total')->default(100);
                $table->integer('stock_current')->default(100);

                $table->index('canteen_id', 'fk_menu_canteens');
                $table->index('meal_id', 'fk_menu_meals');
                $table->foreign('canteen_id', 'fk_menu_canteens')->references('id')->on('canteens');
                $table->foreign('meal_id', 'fk_menu_meals')->references('id')->on('meals');
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('menu_item_id');
                $table->decimal('price_paid', 10, 2);
                $table->enum('status', ['ordered', 'collected', 'cancelled', 'in_exchange'])->default('ordered');
                $table->timestamp('created_at')->useCurrent();

                $table->index('user_id', 'fk_orders_users');
                $table->index('menu_item_id', 'fk_orders_menu');
                $table->foreign('user_id', 'fk_orders_users')->references('id')->on('users');
                $table->foreign('menu_item_id', 'fk_orders_menu')->references('id')->on('menu_items');
            });
        }

        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 45);
            });
        }

        if (!Schema::hasTable('payment_statuses')) {
            Schema::create('payment_statuses', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 45);
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('status_id');
                $table->unsignedInteger('method_id');
                $table->decimal('amount', 10, 2);
                $table->decimal('balance_before', 10, 2);
                $table->decimal('balance_after', 10, 2);
                $table->string('external_transaction_id', 100)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('user_id', 'fk_payments_user');
                $table->index('status_id', 'fk_payments_status');
                $table->index('method_id', 'fk_payments_method');
                $table->foreign('user_id', 'fk_payments_user')->references('id')->on('users');
                $table->foreign('status_id', 'fk_payments_status')->references('id')->on('payment_statuses');
                $table->foreign('method_id', 'fk_payments_method')->references('id')->on('payment_methods');
            });
        }

        if (!Schema::hasTable('exchange')) {
            Schema::create('exchange', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('order_id');
                $table->unsignedInteger('seller_id');
                $table->unsignedInteger('buyer_id')->nullable();
                $table->decimal('listing_price', 10, 2);
                $table->enum('status', ['active', 'sold', 'expired'])->default('active');

                $table->index('order_id', 'fk_burza_order');
                $table->index('seller_id', 'fk_burza_seller');
                $table->foreign('order_id', 'fk_burza_order')->references('id')->on('orders');
                $table->foreign('seller_id', 'fk_burza_seller')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('ingredients')) {
            Schema::create('ingredients', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->enum('unit', ['kg', 'g', 'l', 'ml', 'ks'])->default('kg');
                $table->decimal('stock_quantity', 10, 3)->default(0);
                $table->decimal('min_limit', 10, 3)->default(0);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('meal_ingredients')) {
            Schema::create('meal_ingredients', function (Blueprint $table) {
                $table->unsignedInteger('meal_id');
                $table->unsignedInteger('ingredient_id');
                $table->decimal('amount', 10, 3)->comment('Mnozstvo na jednu porciu');

                $table->primary(['meal_id', 'ingredient_id']);
                $table->index('ingredient_id', 'fk_meals_has_ingredients_ingredient');
                $table->foreign('meal_id', 'fk_meals_has_ingredients_meal')->references('id')->on('meals')->cascadeOnDelete();
                $table->foreign('ingredient_id', 'fk_meals_has_ingredients_ingredient')->references('id')->on('ingredients')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('meals_has_allergens')) {
            Schema::create('meals_has_allergens', function (Blueprint $table) {
                $table->unsignedInteger('meals_id');
                $table->unsignedInteger('allergens_id');

                $table->primary(['meals_id', 'allergens_id']);
                $table->index('allergens_id', 'fk_meals_has_allergens_allergens1_idx');
                $table->index('meals_id', 'fk_meals_has_allergens_meals1_idx');
                $table->foreign('allergens_id', 'fk_meals_has_allergens_allergens1')->references('id')->on('allergens');
                $table->foreign('meals_id', 'fk_meals_has_allergens_meals1')->references('id')->on('meals');
            });
        }

        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('slug')->nullable()->unique();
                $table->string('title_sk')->nullable();
                $table->string('title_en')->nullable();
                $table->string('title_ua')->nullable();
                $table->string('title_ru')->nullable();
                $table->longText('content_sk')->nullable();
                $table->longText('content_en')->nullable();
                $table->longText('content_ua')->nullable();
                $table->longText('content_ru')->nullable();
                $table->string('image_path')->nullable();
                $table->boolean('is_published')->nullable();
                $table->timestamps();
                $table->unsignedInteger('users_id');

                $table->index('users_id', 'fk_articles_users1_idx');
                $table->foreign('users_id', 'fk_articles_users1')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('articles_has_canteens')) {
            Schema::create('articles_has_canteens', function (Blueprint $table) {
                $table->unsignedInteger('articles_id');
                $table->unsignedInteger('canteens_id');

                $table->primary(['articles_id', 'canteens_id']);
                $table->index('canteens_id', 'fk_articles_has_canteens_canteens1_idx');
                $table->index('articles_id', 'fk_articles_has_canteens_articles1_idx');
                $table->foreign('articles_id', 'fk_articles_has_canteens_articles1')->references('id')->on('articles');
                $table->foreign('canteens_id', 'fk_articles_has_canteens_canteens1')->references('id')->on('canteens');
            });
        }

        if (!Schema::hasTable('article_revisions')) {
            Schema::create('article_revisions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('article_id');
                $table->unsignedInteger('users_id');
                $table->string('title_sk')->nullable();
                $table->longText('content_sk')->nullable();
                $table->longText('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('article_id', 'fk_revisions_article');
                $table->index('users_id', 'fk_revisions_user');
                $table->foreign('article_id', 'fk_revisions_article')->references('id')->on('articles')->cascadeOnDelete();
                $table->foreign('users_id', 'fk_revisions_user')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('canteen_closures')) {
            Schema::create('canteen_closures', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('canteen_id');
                $table->date('date');
                $table->boolean('is_closed')->default(true);
                $table->time('open_time')->nullable();
                $table->time('close_time')->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['canteen_id', 'date'], 'uq_canteen_closure_date');
                $table->index('date', 'idx_canteen_closures_date');
                $table->index(['canteen_id', 'date'], 'idx_canteen_closures_canteen_date');
                $table->foreign('canteen_id', 'fk_canteen_closures_canteen')->references('id')->on('canteens')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteen_closures');
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('articles_has_canteens');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('meals_has_allergens');
        Schema::dropIfExists('meal_ingredients');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('exchange');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('canteens');
        Schema::dropIfExists('allergens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
