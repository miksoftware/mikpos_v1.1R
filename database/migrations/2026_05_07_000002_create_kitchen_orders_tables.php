<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track per-item whether it has been sent to its preparation station already.
        // This way we can distinguish between items already on the comanda and new items.
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('preparation_station_id');
        });

        // Kitchen orders — one per (cuenta, station, batch). A single "send" event
        // from the Mostrador may generate several kitchen orders (one per station).
        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->index();              // KO-YYYYMMDD-XXXX
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('mesa_id')->nullable()->constrained('mesas')->nullOnDelete();
            $table->foreignId('preparation_station_id')->nullable()
                ->constrained('preparation_stations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // pending   = enviado, sin tomar
            // preparing = en preparación
            // ready     = listo para entregar
            // delivered = entregado
            // cancelled = cancelado
            $table->enum('status', ['pending', 'preparing', 'ready', 'delivered', 'cancelled'])
                ->default('pending')->index();

            $table->unsignedInteger('items_count')->default(0);
            $table->text('notes')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['preparation_station_id', 'status']);
        });

        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_id')->constrained('kitchen_orders')->cascadeOnDelete();
            $table->foreignId('cuenta_item_id')->nullable()->constrained('cuenta_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'preparing', 'ready', 'delivered', 'cancelled'])
                ->default('pending')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
        Schema::dropIfExists('kitchen_orders');

        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
