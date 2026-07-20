<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_zokan_ratios', function (Blueprint $table) {
            $table->foreignId('stem_id')
                ->nullable()
                ->after('type')
                ->comment('蔵干の天干ID')
                ->constrained('master_stems');
        });

        $stemIds = DB::table('master_stems')->pluck('id', 'name');

        foreach ($this->zokanStemMap() as $row) {
            $stemId = $stemIds[$row['stem_name']] ?? null;

            if ($stemId === null) {
                continue;
            }

            DB::table('master_zokan_ratios')
                ->where('branch_id', $row['branch_id'])
                ->where('type', $row['type'])
                ->update(['stem_id' => $stemId]);
        }

        // TODO: PostgreSQL 本番データで全既存行の backfill を確認後、stem_id を NOT NULL 化する。
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_zokan_ratios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stem_id');
        });
    }

    /**
     * @return array<int, array{branch_id: int, type: string, stem_name: string}>
     */
    private function zokanStemMap(): array
    {
        return [
            ['branch_id' => 1, 'type' => 'yoki', 'stem_name' => '壬'],
            ['branch_id' => 1, 'type' => 'honki', 'stem_name' => '癸'],
            ['branch_id' => 2, 'type' => 'yoki', 'stem_name' => '癸'],
            ['branch_id' => 2, 'type' => 'chuki', 'stem_name' => '辛'],
            ['branch_id' => 2, 'type' => 'honki', 'stem_name' => '己'],
            ['branch_id' => 3, 'type' => 'yoki', 'stem_name' => '戊'],
            ['branch_id' => 3, 'type' => 'chuki', 'stem_name' => '丙'],
            ['branch_id' => 3, 'type' => 'honki', 'stem_name' => '甲'],
            ['branch_id' => 4, 'type' => 'yoki', 'stem_name' => '甲'],
            ['branch_id' => 4, 'type' => 'honki', 'stem_name' => '乙'],
            ['branch_id' => 5, 'type' => 'yoki', 'stem_name' => '乙'],
            ['branch_id' => 5, 'type' => 'chuki', 'stem_name' => '癸'],
            ['branch_id' => 5, 'type' => 'honki', 'stem_name' => '戊'],
            ['branch_id' => 6, 'type' => 'yoki', 'stem_name' => '庚'],
            ['branch_id' => 6, 'type' => 'chuki', 'stem_name' => '戊'],
            ['branch_id' => 6, 'type' => 'honki', 'stem_name' => '丙'],
            ['branch_id' => 7, 'type' => 'yoki', 'stem_name' => '丙'],
            ['branch_id' => 7, 'type' => 'chuki', 'stem_name' => '己'],
            ['branch_id' => 7, 'type' => 'honki', 'stem_name' => '丁'],
            ['branch_id' => 8, 'type' => 'yoki', 'stem_name' => '丁'],
            ['branch_id' => 8, 'type' => 'chuki', 'stem_name' => '乙'],
            ['branch_id' => 8, 'type' => 'honki', 'stem_name' => '己'],
            ['branch_id' => 9, 'type' => 'yoki', 'stem_name' => '戊'],
            ['branch_id' => 9, 'type' => 'chuki', 'stem_name' => '壬'],
            ['branch_id' => 9, 'type' => 'honki', 'stem_name' => '庚'],
            ['branch_id' => 10, 'type' => 'yoki', 'stem_name' => '庚'],
            ['branch_id' => 10, 'type' => 'honki', 'stem_name' => '辛'],
            ['branch_id' => 11, 'type' => 'yoki', 'stem_name' => '辛'],
            ['branch_id' => 11, 'type' => 'chuki', 'stem_name' => '丁'],
            ['branch_id' => 11, 'type' => 'honki', 'stem_name' => '戊'],
            ['branch_id' => 12, 'type' => 'yoki', 'stem_name' => '甲'],
            ['branch_id' => 12, 'type' => 'honki', 'stem_name' => '壬'],
        ];
    }
};
