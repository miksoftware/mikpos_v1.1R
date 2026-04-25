<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['libre', 'ocupada'])->default('libre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
        Schema::dropIfExists('sectores');
    }
};
