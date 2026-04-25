<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->after('includes_tax')->constrained('taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Tax::class);
            $table->dropColumn('tax_id');
        });
    }
};
