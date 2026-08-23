<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kitchen_order_id')->nullable()->constrained('kitchen_orders')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('mesa_id')->nullable()->constrained('mesas')->nullOnDelete();
            $table->foreignId('preparation_station_id')->nullable()->constrained('preparation_stations')->nullOnDelete();

            $table->string('station_name', 100);
            $table->string('station_icon', 50)->nullable();
            $table->string('station_color', 30)->nullable();
            $table->string('mesa_name', 100)->default('Sin mesa');
            $table->string('order_number', 50)->nullable();
            
            // ready = Listo para entregar, delivered = Entregado
            $table->enum('status', ['ready', 'delivered'])->default('ready');
            $table->string('title');
            $table->text('message');

            $table->boolean('read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_notifications');
    }
};
