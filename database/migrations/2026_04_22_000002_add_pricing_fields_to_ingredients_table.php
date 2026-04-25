<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete()->after('name');
            $table->decimal('stock', 10, 2)->nullable()->after('unit_id');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('stock');
            $table->decimal('sale_price', 10, 2)->nullable()->after('purchase_price');
            $table->boolean('includes_tax')->default(false)->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'stock', 'purchase_price', 'sale_price', 'includes_tax']);
        });
    }
};
