<?php

namespace Tests\Unit;

use App\Services\PhotoValidationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhotoValidationServiceTest extends TestCase
{
    public function test_photo_validation_uses_groq_chat_completions(): void
    {
        Config::set('services.groq.key', 'test-groq-key');
        Config::set('services.groq.model', 'qwen/qwen3.6-27b');
        Config::set('services.groq.url', 'https://api.groq.com/openai/v1/chat/completions');

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'valid' => false,
                                'issues' => ['KTP tidak terlihat jelas'],
                                'suggestion' => 'Tempelkan KTP di samping tubuh dan ulangi foto.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = app(PhotoValidationService::class)->validate(
            UploadedFile::fake()->image('front.jpg', 600, 900),
            'ktp',
            'front',
        );

        $this->assertFalse($result['valid']);
        $this->assertSame(['KTP tidak terlihat jelas'], $result['issues']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-groq-key')
                && $payload['model'] === 'qwen/qwen3.6-27b'
                && $payload['messages'][0]['content'][1]['type'] === 'image_url'
                && str_starts_with($payload['messages'][0]['content'][1]['image_url']['url'], 'data:image/jpeg;base64,');
        });
    }
}
