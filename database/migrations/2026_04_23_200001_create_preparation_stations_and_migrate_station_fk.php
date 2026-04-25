<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create preparation_stations table
        Schema::create('preparation_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('icon', 10)->nullable();    // emoji
            $table->string('color', 7)->nullable();   // #rrggbb
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Seed default stations matching old ENUM values
        DB::table('preparation_stations')->insert([
            ['name' => 'Cocina',     'icon' => '🍳', 'color' => '#16a34a', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bar',        'icon' => '🍹', 'color' => '#2563eb', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Repostería', 'icon' => '🍰', 'color' => '#db2777', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $cocinaId     = DB::table('preparation_stations')->where('name', 'Cocina')->value('id');
        $barId        = DB::table('preparation_stations')->where('name', 'Bar')->value('id');
        $reposteriaId = DB::table('preparation_stations')->where('name', 'Repostería')->value('id');

        // 3. Products: add FK, migrate data, drop ENUM
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('preparation_station_id')->nullable()->after('product_type');
        });
        DB::table('products')->where('station', 'cocina')->update(['preparation_station_id' => $cocinaId]);
        DB::table('products')->where('station', 'bar')->update(['preparation_station_id' => $barId]);
        DB::table('products')->where('station', 'reposteria')->update(['preparation_station_id' => $reposteriaId]);
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('station');
        });

        // 4. Ingredients: add FK, migrate data, drop ENUM
        Schema::table('ingredients', function (Blueprint $table) {
            $table->unsignedBigInteger('preparation_station_id')->nullable()->after('is_active');
        });
        DB::table('ingredients')->where('station', 'cocina')->update(['preparation_station_id' => $cocinaId]);
        DB::table('ingredients')->where('station', 'bar')->update(['preparation_station_id' => $barId]);
        DB::table('ingredients')->where('station', 'reposteria')->update(['preparation_station_id' => $reposteriaId]);
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('station');
        });

        // 5. CuentaItems: add FK, migrate data, drop ENUM
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->unsignedBigInteger('preparation_station_id')->nullable()->after('notes');
        });
        DB::table('cuenta_items')->where('station', 'cocina')->update(['preparation_station_id' => $cocinaId]);
        DB::table('cuenta_items')->where('station', 'bar')->update(['preparation_station_id' => $barId]);
        DB::table('cuenta_items')->where('station', 'reposteria')->update(['preparation_station_id' => $reposteriaId]);
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->dropColumn('station');
        });
    }

    public function down(): void
    {
        Schema::table('cuenta_items', function (Blueprint $table) {
            $table->dropColumn('preparation_station_id');
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('notes');
        });
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('preparation_station_id');
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('is_active');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('preparation_station_id');
            $table->enum('station', ['cocina', 'bar', 'reposteria'])->nullable()->after('product_type');
        });
        Schema::dropIfExists('preparation_stations');
    }
};
