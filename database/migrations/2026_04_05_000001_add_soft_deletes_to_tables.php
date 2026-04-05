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
        Schema::table('canteens', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
