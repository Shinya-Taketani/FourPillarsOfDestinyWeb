<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBirthDateTime;
use App\Services\CalendarCoverageService;
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

    /** @return array<string,array<int,string>> */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'birthday' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.CalendarCoverageService::BIRTH_MIN_YEAR.'-01-01',
                'before_or_equal:'.CalendarCoverageService::BIRTH_MAX_YEAR.'-12-31',
            ],
            'birth_time' => ['required', 'date_format:H:i'],
            'gender' => ['required', 'in:male,female'],
            'longitude' => ['required', 'numeric', 'between:120,150'],
            'target_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'target_datetime' => [
                'nullable',
                'date',
                'after_or_equal:'.CalendarCoverageService::BIRTH_MIN_YEAR.'-01-01 00:00:00',
                'before_or_equal:'.CalendarCoverageService::BIRTH_MAX_YEAR.'-12-31 23:59:59',
            ],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'birthday.after_or_equal' => '生年月日は1900年から2100年の範囲で入力してください。',
            'birthday.before_or_equal' => '生年月日は1900年から2100年の範囲で入力してください。',
            'target_datetime.after_or_equal' => '対象日時は1900年から2100年の範囲で入力してください。',
            'target_datetime.before_or_equal' => '対象日時は1900年から2100年の範囲で入力してください。',
        ];
    }

    /** @return array<string,string> */
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

    /** @return array{name:string,birth_datetime:string,gender:string,longitude:float,target_datetime:?string} */
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
