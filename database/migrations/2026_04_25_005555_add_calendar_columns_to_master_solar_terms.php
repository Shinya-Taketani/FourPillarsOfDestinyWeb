<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_solar_terms', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable(); // 節入り日時
            $table->integer('month_stem_id')->nullable(); // 月干ID
            $table->integer('month_branch_id')->nullable(); // 月支ID
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_solar_terms', function (Blueprint $table) {
            //
        });
    }
};
