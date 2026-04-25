<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->dropColumn('station');
        });
    }
};
