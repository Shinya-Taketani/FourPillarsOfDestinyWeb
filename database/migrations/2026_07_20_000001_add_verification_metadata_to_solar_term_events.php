<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solar_term_events', function (Blueprint $table) {
            $table->string('precision_level', 20)->default('minute');
            $table->date('source_accessed_on')->nullable();
            $table->text('source_citation_text')->nullable();
            $table->string('raw_content_hash', 64)->nullable();
            $table->string('verification_status', 30)->default('imported')->index();
        });
    }

    public function down(): void
    {
        Schema::table('solar_term_events', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'precision_level',
                'source_accessed_on',
                'source_citation_text',
                'raw_content_hash',
                'verification_status',
            ]);
        });
    }
};
