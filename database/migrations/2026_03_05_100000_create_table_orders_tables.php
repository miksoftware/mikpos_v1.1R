<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open'); // open, closed, cancelled
            $table->text('observations')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('table_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_order_id')->constrained('table_orders')->cascadeOnDelete();
            $table->string('item_type', 20); // product, service, ingredient
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('subtotal', 12, 2);
            $table->json('group_selections')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_order_items');
        Schema::dropIfExists('table_orders');
    }
};
