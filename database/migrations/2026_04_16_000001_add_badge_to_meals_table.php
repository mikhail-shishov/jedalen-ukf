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
        if (!Schema::hasTable('meals')) {
            return;
        }

        Schema::table('meals', function (Blueprint $table) {
            if (!Schema::hasColumn('meals', 'badge')) {
                $table->string('badge')->nullable()->after('image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('meals')) {
            return;
        }

        Schema::table('meals', function (Blueprint $table) {
            if (Schema::hasColumn('meals', 'badge')) {
                $table->dropColumn('badge');
            }
        });
    }
};
