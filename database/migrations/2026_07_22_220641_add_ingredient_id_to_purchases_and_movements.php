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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('ingredient_id')->nullable()->after('product_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('ingredient_id')->nullable()->after('product_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn('ingredient_id');
            // Reverting product_id to non-nullable might fail if there are nulls, but standard rollback behavior
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn('ingredient_id');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
