<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('scientific_name', 160)->nullable()->after('name');
            $table->decimal('adult_size_cm', 5, 1)->nullable()->after('max_temp_c');
            $table->unsignedSmallInteger('minimum_tank_liters')->nullable()->after('adult_size_cm');
            $table->unsignedSmallInteger('minimum_group_size')->nullable()->after('minimum_tank_liters');
            $table->string('care_level', 20)->nullable()->after('minimum_group_size');
            $table->string('care_notes', 500)->nullable()->after('care_level');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_search_idx');
            DB::statement(<<<'SQL'
                CREATE INDEX products_search_idx ON products USING GIN (
                    to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(scientific_name, '') || ' ' ||
                        coalesce(description, '') || ' ' || coalesce(brand, '') || ' ' || coalesce(sku, ''))
                );
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_search_idx');
            DB::statement(<<<'SQL'
                CREATE INDEX products_search_idx ON products USING GIN (
                    to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' ||
                        coalesce(brand, '') || ' ' || coalesce(sku, ''))
                )
            SQL);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'scientific_name', 'adult_size_cm', 'minimum_tank_liters',
                'minimum_group_size', 'care_level', 'care_notes',
            ]);
        });
    }
};
