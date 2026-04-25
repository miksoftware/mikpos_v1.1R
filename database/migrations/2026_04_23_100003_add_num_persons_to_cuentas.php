<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->unsignedTinyInteger('num_persons')->default(1)->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropColumn('num_persons');
        });
    }
};
