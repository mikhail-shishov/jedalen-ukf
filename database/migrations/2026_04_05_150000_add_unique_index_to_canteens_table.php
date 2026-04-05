<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->unique(['name', 'address'], 'canteens_name_address_unique');
        });
    }

    public function down(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->dropUnique('canteens_name_address_unique');
        });
    }
};