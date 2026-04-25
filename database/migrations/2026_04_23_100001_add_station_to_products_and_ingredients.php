<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('product_type');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('station');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('station');
        });
    }
};
