<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBirthDateTime;
use Illuminate\Foundation\Http\FormRequest;

class CompatibilityAnalyzeRequest extends FormRequest
{
    use NormalizesBirthDateTime;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $person1 = $this->input('person1', []);
        $person2 = $this->input('person2', []);

        if (is_array($person1)) {
            [$birthday, $birthTime] = $this->splitBirthDateTime($person1['birthday'] ?? null, $person1['birth_time'] ?? null);
            $person1['birthday'] = $birthday;
            $person1['birth_time'] = $birthTime;
        }

        if (is_array($person2)) {
            [$birthday, $birthTime] = $this->splitBirthDateTime($person2['birthday'] ?? null, $person2['birth_time'] ?? null);
            $person2['birthday'] = $birthday;
            $person2['birth_time'] = $birthTime;
        }

        $this->merge([
            'person1' => $person1,
            'person2' => $person2,
        ]);
    }

    public function rules(): array
    {
        return [
            'person1' => ['required', 'array'],
            'person1.name' => ['required', 'string', 'max:100'],
            'person1.birthday' => ['required', 'date_format:Y-m-d'],
            'person1.birth_time' => ['required', 'date_format:H:i'],
            'person1.gender' => ['required', 'in:male,female'],
            'person1.longitude' => ['required', 'numeric', 'between:120,150'],
            'person2' => ['required', 'array'],
            'person2.name' => ['required', 'string', 'max:100'],
            'person2.birthday' => ['required', 'date_format:Y-m-d'],
            'person2.birth_time' => ['required', 'date_format:H:i'],
            'person2.gender' => ['required', 'in:male,female'],
            'person2.longitude' => ['required', 'numeric', 'between:120,150'],
            'target_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'target_datetime' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'person1' => '1人目',
            'person1.name' => '1人目の氏名',
            'person1.birthday' => '1人目の生年月日',
            'person1.birth_time' => '1人目の出生時刻',
            'person1.gender' => '1人目の性別',
            'person1.longitude' => '1人目の出生地経度',
            'person2' => '2人目',
            'person2.name' => '2人目の氏名',
            'person2.birthday' => '2人目の生年月日',
            'person2.birth_time' => '2人目の出生時刻',
            'person2.gender' => '2人目の性別',
            'person2.longitude' => '2人目の出生地経度',
            'target_year' => '対象年',
            'target_datetime' => '対象日時',
        ];
    }

    public function validatedForCompatibility(): array
    {
        $validated = $this->validated();

        foreach (['person1', 'person2'] as $key) {
            $validated[$key]['birth_datetime'] = $this->combineBirthDateTime($validated[$key]);
            $validated[$key]['longitude'] = (float) $validated[$key]['longitude'];
        }

        $validated['target_datetime'] = $this->resolveTargetDateTime($validated);

        return $validated;
    }
}
