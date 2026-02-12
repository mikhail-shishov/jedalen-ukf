<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $blueprint) {
            $blueprint->id();
            
            // name in the currect format - e.g. Kur.prs.pln.sunk.
            $blueprint->string('raw_name')->unique()->index();
            
            $blueprint->string('allergens')->nullable();
            
            // improved name
            $blueprint->string('name_sk')->nullable();
            $blueprint->string('name_en')->nullable();
            $blueprint->string('name_ua')->nullable();
            $blueprint->string('name_ru')->nullable();
            
            $blueprint->string('image_path')->nullable();

            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};