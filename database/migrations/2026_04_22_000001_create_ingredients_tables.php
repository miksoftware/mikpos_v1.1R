<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('manage_inventory')->default(false);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ingredient_group_ingredient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_group_id')->constrained('ingredient_groups')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_group_ingredient');
        Schema::dropIfExists('ingredient_groups');
        Schema::dropIfExists('ingredients');
    }
};
