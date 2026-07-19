<?php

namespace Tests\Feature;

use App\Support\PdfFileNameSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PdfRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_file_name_parts_are_sanitized(): void
    {
        $this->assertSame('山田_太郎', PdfFileNameSanitizer::part('山田/太郎'));
        $this->assertSame('山田_太郎', PdfFileNameSanitizer::part("山田\n太郎"));
        $this->assertSame('secret', PdfFileNameSanitizer::part('../secret'));
        $this->assertSame('unknown', PdfFileNameSanitizer::part('/\\:*?"<>|'));
        $this->assertSame('unknown', PdfFileNameSanitizer::part('!!!'));
        $this->assertLessThanOrEqual(80, mb_strlen(PdfFileNameSanitizer::part(str_repeat('あ', 120))));
        $this->assertStringEndsWith('.pdf', PdfFileNameSanitizer::appraisal('山田/太郎'));
        $this->assertStringEndsWith('.pdf', PdfFileNameSanitizer::compatibility("山田\n太郎", '../secret'));
    }

    public function test_appraisal_pdf_returns_pdf_with_sanitized_file_name(): void
    {
        $this->seedCalendarEvents();

        $response = $this->postJson(route('appraisal.pdf'), array_merge($this->validAppraisalPayload(), [
            'name' => "山田/太郎\n../secret",
        ]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.pdf', $disposition);
        $this->assertStringNotContainsString('/', $disposition);
        $this->assertStringNotContainsString('\\', $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
    }

    public function test_compatibility_pdf_returns_pdf_with_sanitized_file_name(): void
    {
        $this->seedCalendarEvents();

        $payload = $this->validCompatibilityPayload();
        $payload['person1']['name'] = '山田/太郎';
        $payload['person2']['name'] = "../secret\n相手";

        $response = $this->postJson(route('compatibility.pdf'), $payload);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.pdf', $disposition);
        $this->assertStringNotContainsString('/', $disposition);
        $this->assertStringNotContainsString('\\', $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
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

    public function test_pdf_generation_exception_returns_safe_json_response(): void
    {
        $this->seedCalendarEvents();

        Pdf::shouldReceive('setOption')->once();
        Pdf::shouldReceive('loadView')
            ->once()
            ->andThrow(new RuntimeException('secret path /var/www/FourPillarsOfDestinyWeb'));

        $response = $this->postJson(route('appraisal.pdf'), $this->validAppraisalPayload());

        $response->assertStatus(500)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'PDFの生成に失敗しました。時間をおいて再度お試しください。');

        $this->assertStringNotContainsString('/var/www', $response->getContent());
        $this->assertStringNotContainsString('secret path', $response->getContent());
    }

    private function validAppraisalPayload(): array
    {
        return [
            'name' => '検証者',
            'birthday' => '2026-03-05',
            'birth_time' => '12:00',
            'gender' => 'male',
            'longitude' => 135.0,
            'target_datetime' => '2026-07-01T00:00',
        ];
    }

    private function validCompatibilityPayload(): array
    {
        return [
            'person1' => [
                'name' => '自分',
                'birthday' => '2026-03-05',
                'birth_time' => '12:00',
                'gender' => 'male',
                'longitude' => 135.0,
            ],
            'person2' => [
                'name' => '相手',
                'birthday' => '2026-03-06',
                'birth_time' => '12:00',
                'gender' => 'female',
                'longitude' => 135.0,
            ],
            'target_datetime' => '2026-07-01T00:00',
        ];
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
