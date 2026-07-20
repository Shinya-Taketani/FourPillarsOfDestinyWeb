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
        Schema::create('solar_term_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique()->comment('節気名');
            $table->decimal('longitude_degree', 5, 2)->comment('太陽黄経');
            $table->string('term_type', 20)->comment('major_term または middle_term');
            $table->foreignId('month_branch_id')->nullable()->constrained('master_branches');
            $table->unsignedSmallInteger('display_order')->index();
            $table->boolean('is_month_boundary')->default(false)->index();
            $table->timestamps();

            $table->index(['term_type', 'is_month_boundary']);
        });

        Schema::create('solar_term_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_term_definition_id')->constrained('solar_term_definitions')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->dateTime('started_at')->index()->comment('節入り日時');
            $table->string('timezone', 64);
            $table->string('calendar_system', 50)->index();
            $table->string('source_title');
            $table->text('source_url')->nullable();
            $table->string('source_rank', 20);
            $table->boolean('adopted')->default(false)->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(
                ['year', 'solar_term_definition_id', 'source_rank'],
                'solar_term_events_year_definition_source_unique'
            );
            $table->index(['year', 'solar_term_definition_id'], 'solar_term_events_year_definition_index');
            $table->index(['adopted', 'started_at'], 'solar_term_events_adopted_started_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_term_events');
        Schema::dropIfExists('solar_term_definitions');
    }
};
