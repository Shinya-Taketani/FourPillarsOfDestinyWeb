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
        // --- 1. 認証・ユーザー系 ---
        /*
        Schema::create('users', function (Blueprint $table) {
            $table->comment('利用者管理：鑑定師・一般ユーザー');
            $table->id();
            $table->string('name')->comment('表示名');
            $table->string('email')->unique()->comment('ログイン用メール');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('ハッシュ化パスワード');
            $table->rememberToken();
            $table->timestamps();
        });
        */

        // --- 2. 四柱推命：基礎マスター ---
        Schema::create('master_elements', function (Blueprint $table) {
            $table->comment('五行マスター：木火土金水の定義');
            $table->id()->comment('五行ID');
            $table->string('name', 10)->comment('五行名称');
            $table->string('color_code', 7)->comment('UI用カラーコード');
        });

        Schema::create('master_stems', function (Blueprint $table) {
            $table->comment('十干マスター：天干（甲〜癸）の定義');
            $table->id()->comment('十干ID');
            $table->string('name', 10)->comment('十干名称');
            $table->foreignId('element_id')->comment('五行ID')->constrained('master_elements');
            $table->boolean('is_yang')->comment('陰陽フラグ (1:陽, 0:陰)');
        });

        Schema::create('master_branches', function (Blueprint $table) {
            $table->comment('十二支マスター：地支（子〜亥）の定義');
            $table->id()->comment('十二支ID');
            $table->string('name', 10)->comment('十二支名称');
            $table->foreignId('element_id')->comment('五行ID')->constrained('master_elements');
            $table->integer('season_id')->comment('季節ID (1:春, 2:夏, 3:土用, 4:秋, 5:冬)');
        });

        // --- 3. 泰山流：計算・ロジックマスター ---
        Schema::create('master_zokan_ratios', function (Blueprint $table) {
            $table->comment('蔵干比率：泰山流（余気・中気・本気）の配分定義');
            $table->id();
            $table->foreignId('branch_id')->comment('十二支ID')->constrained('master_branches');
            $table->string('type', 10)->comment('区分 (yoki, chuki, honki)');
            $table->foreignId('element_id')->comment('対応五行ID')->constrained('master_elements');
            $table->integer('days')->comment('該当日数');
        });

        Schema::create('master_seasonal_multipliers', function (Blueprint $table) {
            $table->comment('季節倍率：天保元始暦に基づく旺相死囚休の力量倍率');
            $table->id();
            $table->integer('season_id')->comment('季節ID');
            $table->foreignId('element_id')->comment('対象五行ID')->constrained('master_elements');
            $table->string('state_name', 10)->comment('旺相死囚休名称');
            $table->decimal('multiplier', 3, 2)->comment('力量倍率');
        });

        Schema::create('master_ten_gods', function (Blueprint $table) {
            $table->comment('通変星マスター：比肩〜印綬');
            $table->id();
            $table->string('name', 20)->comment('星の名称');
            $table->text('description')->nullable()->comment('意味解説');
        });

        Schema::create('master_twelve_life_stages', function (Blueprint $table) {
            $table->comment('十二運マスター：胎〜墓');
            $table->id();
            $table->string('name', 20)->comment('名称');
            $table->integer('score')->comment('力量計算用基礎点');
            $table->text('description')->nullable()->comment('運勢・性格の解説');
        });

        Schema::create('master_solar_terms', function (Blueprint $table) {
            $table->comment('二十四節気：天保元始暦（定気法）基準値');
            $table->id();
            $table->string('name', 20)->comment('節気名');
            $table->decimal('longitude_degree', 5, 2)->comment('基準黄経');
            $table->text('description')->nullable();
        });

        // --- 4. 相性・鑑定マスター ---
        Schema::create('master_stem_affinities', function (Blueprint $table) {
            $table->comment('日干相性：干合・親和性スコア');
            $table->id();
            $table->foreignId('stem_id_1')->comment('日干1')->constrained('master_stems');
            $table->foreignId('stem_id_2')->comment('日干2')->constrained('master_stems');
            $table->integer('affinity_score')->comment('相性スコア');
        });

        Schema::create('master_compatibility_labels', function (Blueprint $table) {
            $table->comment('相性判定ラベル');
            $table->id();
            $table->string('label_name', 20)->comment('ラベル (大吉〜大凶)');
            $table->integer('min_score')->comment('下限点');
            $table->integer('max_score')->comment('上限点');
        });

        // --- 5. 実データ・ログ系 ---
        Schema::create('analysis_targets', function (Blueprint $table) {
            $table->comment('鑑定対象者：顧客カルテ');
            $table->id();
            $table->foreignId('user_id')->comment('作成ユーザー')->constrained('users');
            $table->string('name', 100)->comment('姓名');
            $table->smallInteger('gender')->nullable()->comment('1:男, 2:女');
            $table->dateTime('birthday')->comment('出生日時');
            $table->string('birth_place')->nullable()->comment('出生地名');
            $table->decimal('longitude', 5, 2)->nullable()->comment('経度');
            $table->text('memo')->nullable()->comment('鑑定メモ');
            $table->timestamps();
        });

        Schema::create('analysis_logs', function (Blueprint $table) {
            $table->comment('鑑定履歴：詳細な計算結果');
            $table->id();
            $table->foreignId('target_id')->comment('対象者ID')->constrained('analysis_targets');
            $table->jsonb('chart_data')->comment('命式・スコア詳細(JSONB)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 外部キー制約を考慮し、作成した時と逆順で削除
        Schema::dropIfExists('analysis_logs');
        Schema::dropIfExists('analysis_targets');
        Schema::dropIfExists('master_compatibility_labels');
        Schema::dropIfExists('master_stem_affinities');
        Schema::dropIfExists('master_solar_terms');
        Schema::dropIfExists('master_twelve_life_stages');
        Schema::dropIfExists('master_ten_gods');
        Schema::dropIfExists('master_seasonal_multipliers');
        Schema::dropIfExists('master_zokan_ratios');
        Schema::dropIfExists('master_branches');
        Schema::dropIfExists('master_stems');
        Schema::dropIfExists('master_elements');
        Schema::dropIfExists('users');
    }
};