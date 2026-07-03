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
        Schema::table('product_ingredient_groups', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('ingredient_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_ingredient_groups', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
