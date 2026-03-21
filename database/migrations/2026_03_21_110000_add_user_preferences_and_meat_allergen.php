<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'blocked_allergen_numbers')) {
                    $table->json('blocked_allergen_numbers')->nullable()->after('credit_balance');
                }

                if (!Schema::hasColumn('users', 'push_enabled')) {
                    $table->boolean('push_enabled')->default(false)->after('blocked_allergen_numbers');
                }

                if (!Schema::hasColumn('users', 'push_locale')) {
                    $table->string('push_locale', 8)->nullable()->after('push_enabled');
                }
            });
        }

        if (Schema::hasTable('allergens')) {
            $exists = DB::table('allergens')->where('number', '0')->exists();
            if (!$exists) {
                DB::table('allergens')->insert([
                    'number' => '0',
                    'name' => 'Maso / Meat / Miaso / Miaso',
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('allergens')) {
            DB::table('allergens')->where('number', '0')->delete();
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'push_locale')) {
                    $table->dropColumn('push_locale');
                }

                if (Schema::hasColumn('users', 'push_enabled')) {
                    $table->dropColumn('push_enabled');
                }

                if (Schema::hasColumn('users', 'blocked_allergen_numbers')) {
                    $table->dropColumn('blocked_allergen_numbers');
                }
            });
        }
    }
};
