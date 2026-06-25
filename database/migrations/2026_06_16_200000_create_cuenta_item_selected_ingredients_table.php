<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuenta_item_selected_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_item_id')->constrained('cuenta_items')->cascadeOnDelete();
            $table->foreignId('ingredient_group_id')->constrained('ingredient_groups')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cuenta_item_id', 'ingredient_group_id'], 'ci_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta_item_selected_ingredients');
    }
};
