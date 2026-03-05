<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add product_type to products table
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 20)->default('independent')->after('branch_id');
            // independent = producto independiente (sin ingredientes)
            // composite = producto compuesto (tiene ingredientes)
        });

        // Fixed ingredients for a composite product
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
        });

        // Link composite product to ingredient groups (variable/interchangeable ingredients)
        Schema::create('product_ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_group_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_group_id'], 'product_group_unique');
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
