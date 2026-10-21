<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS products_search_idx
            ON products USING GIN (
                to_tsvector(
                    'simple',
                    coalesce(name, '') || ' ' || coalesce(description, '') || ' ' ||
                    coalesce(brand, '') || ' ' || coalesce(sku, '')
                )
            )
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_search_idx');
        }
    }
};
