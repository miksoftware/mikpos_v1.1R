<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparation_station_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('preparation_station_id')
                ->constrained('preparation_stations')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'preparation_station_id'], 'psu_user_station_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparation_station_user');
    }
};
