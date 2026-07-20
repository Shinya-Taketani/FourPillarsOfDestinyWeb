<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX_NAME = 'solar_term_events_one_adopted_per_term';

    public function up(): void
    {
        DB::statement(sprintf(
            'CREATE UNIQUE INDEX %s ON solar_term_events (year, solar_term_definition_id) WHERE adopted = true',
            self::INDEX_NAME,
        ));
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX_NAME);
    }
};
