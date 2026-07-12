<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBirthDateTime;
use Illuminate\Foundation\Http\FormRequest;

class AppraisalAnalyzeRequest extends FormRequest
{
    use NormalizesBirthDateTime;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        [$birthday, $birthTime] = $this->splitBirthDateTime($this->input('birthday'), $this->input('birth_time'));

        $this->merge([
            'birthday' => $birthday,
            'birth_time' => $birthTime,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'birthday' => ['required', 'date_format:Y-m-d'],
            'birth_time' => ['required', 'date_format:H:i'],
            'gender' => ['required', 'in:male,female'],
            'longitude' => ['required', 'numeric', 'between:120,150'],
            'target_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'target_datetime' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'birthday' => '生年月日',
            'birth_time' => '出生時刻',
            'gender' => '性別',
            'longitude' => '出生地経度',
            'target_year' => '対象年',
            'target_datetime' => '対象日時',
        ];
    }

    public function validatedForAnalysis(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'] ?? '',
            'birth_datetime' => $this->combineBirthDateTime($validated),
            'gender' => $validated['gender'],
            'longitude' => (float) $validated['longitude'],
            'target_datetime' => $this->resolveTargetDateTime($validated),
        ];
    }
}
