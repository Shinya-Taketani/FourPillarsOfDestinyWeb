<?php

namespace Tests\Feature;

use App\Support\PdfFileNameSanitizer;
use Tests\TestCase;

class PdfRequestValidationTest extends TestCase
{
    public function test_pdf_file_name_parts_are_sanitized(): void
    {
        $this->assertSame('山田_太郎', PdfFileNameSanitizer::part('山田/太郎'));
        $this->assertSame('山田_太郎', PdfFileNameSanitizer::part("山田\n太郎"));
        $this->assertSame('unknown', PdfFileNameSanitizer::part('/\\:*?"<>|'));
    }

    public function test_appraisal_pdf_request_rejects_invalid_input_as_json(): void
    {
        $this->postJson(route('appraisal.pdf'), [
            'name' => '検証者',
            'birthday' => '2026-03-05',
            'birth_time' => '12:00',
            'gender' => 'other',
            'longitude' => 135.0,
            'target_datetime' => '2026-07-01T00:00',
        ])->assertUnprocessable();
    }

    public function test_compatibility_pdf_request_rejects_invalid_input_as_json(): void
    {
        $this->postJson(route('compatibility.pdf'), [
            'person1' => [
                'name' => '自分',
                'birthday' => '2026-03-05',
                'birth_time' => '12:00',
                'gender' => 'male',
                'longitude' => 119,
            ],
            'person2' => [
                'name' => '相手',
                'birthday' => '2026-03-06',
                'birth_time' => '12:00',
                'gender' => 'female',
                'longitude' => 135.0,
            ],
            'target_datetime' => '2026-07-01T00:00',
        ])->assertUnprocessable();
    }
}
