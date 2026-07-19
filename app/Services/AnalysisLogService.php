<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalysisLog;
use App\Models\AnalysisTarget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalysisLogService
{
    /**
     * ログイン中の単体鑑定だけを保存する。
     * TODO: 個人情報保存ポリシー要確認。削除・匿名化要件が固まり次第、保存項目を再評価する。
     *
     * @param  array{name:string,birth_datetime:string,gender:string,longitude:float,target_datetime:string}  $validated
     * @param  array<string,mixed>  $result
     */
    public function storeAppraisalIfAuthenticated(array $validated, array $result): ?AnalysisLog
    {
        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        try {
            $target = AnalysisTarget::create([
                'user_id' => $userId,
                'name' => $validated['name'] !== '' ? $validated['name'] : 'unknown',
                'gender' => $this->genderCode($validated['gender']),
                'birthday' => $validated['birth_datetime'],
                'longitude' => $validated['longitude'],
            ]);

            return AnalysisLog::create([
                'target_id' => $target->id,
                'chart_data' => $this->chartSnapshot($result, $validated['target_datetime']),
            ]);
        } catch (Throwable $e) {
            Log::warning('Analysis log save failed.', [
                'user_id' => $userId,
                'exception_class' => $e::class,
            ]);

            report($e);

            return null;
        }
    }

    private function genderCode(string $gender): ?int
    {
        return match ($gender) {
            'male' => 1,
            'female' => 2,
            default => null,
        };
    }

    /**
     * request 全体や個人名・出生日時を chart_data に入れない。
     *
     * @param  array<string,mixed>  $result
     * @return array<string,mixed>
     */
    private function chartSnapshot(array $result, string $targetDateTime): array
    {
        return [
            'schema_version' => $result['schema_version'] ?? 1,
            'target_datetime' => $targetDateTime,
            'chart' => $result['chart'] ?? null,
            'pillars' => $result['pillars'] ?? null,
            'five_elements_scores' => $result['five_elements_scores'] ?? null,
            'dayun_start_age_full' => $result['dayun']['start_age_full'] ?? null,
            'saiun' => $result['saiun'] ?? null,
            'warnings' => $result['warnings'] ?? [],
        ];
    }
}
