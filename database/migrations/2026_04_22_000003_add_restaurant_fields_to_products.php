<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add product_type to products table
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_type', ['normal', 'compuesto'])->default('normal')->after('name');
        });

        // 2. Create product_ingredients pivot table (recipe)
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
        });

        // 3. Create product_ingredient_groups pivot table (elegibles)
        Schema::create('product_ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_group_id')->constrained('ingredient_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredient_groups');
        Schema::dropIfExists('product_ingredients');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });
    }
};
