<?php

namespace Tests\Integration\Services;

use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TranslationService $translationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->translationService = app(TranslationService::class);
        Cache::flush();
    }

    public function test_can_translate_text()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Hola mundo',
                            'detectedSourceLanguage' => 'en'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->translate('Hello world', 'es');

        $this->assertTrue($result['success']);
        $this->assertEquals('Hola mundo', $result['translatedText']);
        $this->assertEquals('en', $result['detectedSourceLanguage']);
    }

    public function test_can_translate_with_source_language()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Bonjour le monde'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->translate('Hello world', 'fr', 'en');

        $this->assertTrue($result['success']);
        $this->assertEquals('Bonjour le monde', $result['translatedText']);
    }

    public function test_caches_translation_results()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Hola mundo',
                            'detectedSourceLanguage' => 'en'
                        ]
                    ]
                ]
            ], 200)
        ]);

        // First call
        $result1 = $this->translationService->translate('Hello world', 'es');
        
        // Second call should use cache
        $result2 = $this->translationService->translate('Hello world', 'es');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertEquals($result1['translatedText'], $result2['translatedText']);

        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    public function test_handles_translation_api_errors()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid request'
                ]
            ], 400)
        ]);

        $result = $this->translationService->translate('Hello world', 'es');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid request', $result['error']);
    }

    public function test_handles_network_errors()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response(null, 500)
        ]);

        $result = $this->translationService->translate('Hello world', 'es');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_can_detect_language()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'detections' => [
                        [
                            [
                                'language' => 'es',
                                'confidence' => 0.95
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->detectLanguage('Hola mundo');

        $this->assertTrue($result['success']);
        $this->assertEquals('es', $result['language']);
        $this->assertEquals(0.95, $result['confidence']);
    }

    public function test_can_get_supported_languages()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'languages' => [
                        ['language' => 'en', 'name' => 'English'],
                        ['language' => 'es', 'name' => 'Spanish'],
                        ['language' => 'fr', 'name' => 'French'],
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->getSupportedLanguages();

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['languages']);
        $this->assertEquals('English', $result['languages'][0]['name']);
    }

    public function test_caches_supported_languages()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'languages' => [
                        ['language' => 'en', 'name' => 'English'],
                        ['language' => 'es', 'name' => 'Spanish'],
                    ]
                ]
            ], 200)
        ]);

        // First call
        $result1 = $this->translationService->getSupportedLanguages();
        
        // Second call should use cache
        $result2 = $this->translationService->getSupportedLanguages();

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertEquals($result1['languages'], $result2['languages']);

        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    public function test_can_translate_multiple_texts()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        ['translatedText' => 'Hola'],
                        ['translatedText' => 'Mundo'],
                        ['translatedText' => 'Adiós'],
                    ]
                ]
            ], 200)
        ]);

        $texts = ['Hello', 'World', 'Goodbye'];
        $result = $this->translationService->translateMultiple($texts, 'es');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['translations']);
        $this->assertEquals('Hola', $result['translations'][0]['translatedText']);
        $this->assertEquals('Mundo', $result['translations'][1]['translatedText']);
        $this->assertEquals('Adiós', $result['translations'][2]['translatedText']);
    }

    public function test_validates_input_parameters()
    {
        $result = $this->translationService->translate('', 'es');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty', $result['error']);
    }

    public function test_validates_language_codes()
    {
        $result = $this->translationService->translate('Hello world', 'invalid-lang');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('language', $result['error']);
    }

    public function test_handles_long_text_translation()
    {
        $longText = str_repeat('This is a long text. ', 100); // ~2000 characters

        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => str_repeat('Este es un texto largo. ', 100)
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->translate($longText, 'es');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Este es un texto largo', $result['translatedText']);
    }

    public function test_respects_rate_limiting()
    {
        // Simulate rate limiting by making multiple rapid requests
        Http::fake([
            'translate.googleapis.com/*' => Http::sequence()
                ->push(['data' => ['translations' => [['translatedText' => 'Hola']]]], 200)
                ->push(['data' => ['translations' => [['translatedText' => 'Mundo']]]], 200)
                ->push(['error' => ['code' => 429, 'message' => 'Rate limit exceeded']], 429)
        ]);

        $result1 = $this->translationService->translate('Hello', 'es');
        $result2 = $this->translationService->translate('World', 'es');
        $result3 = $this->translationService->translate('Test', 'es');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertFalse($result3['success']);
        $this->assertStringContainsString('Rate limit', $result3['error']);
    }

    public function test_can_translate_html_content()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => '<p>Hola <strong>mundo</strong></p>'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $htmlContent = '<p>Hello <strong>world</strong></p>';
        $result = $this->translationService->translateHtml($htmlContent, 'es');

        $this->assertTrue($result['success']);
        $this->assertEquals('<p>Hola <strong>mundo</strong></p>', $result['translatedText']);
    }

    public function test_preserves_html_structure()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Hola mundo'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $htmlContent = '<div class="content"><p>Hello world</p></div>';
        $result = $this->translationService->translateHtml($htmlContent, 'es');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('<div class="content">', $result['translatedText']);
        $this->assertStringContainsString('Hola mundo', $result['translatedText']);
    }

    public function test_logs_translation_usage()
    {
        Http::fake([
            'translate.googleapis.com/*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Hola mundo'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->translationService->translate('Hello world', 'es');

        $this->assertTrue($result['success']);
        
        // Check that usage was logged
        $this->assertDatabaseHas('translation_usage', [
            'source_language' => 'en',
            'target_language' => 'es',
            'character_count' => 11, // "Hello world" length
        ]);
    }

    public function test_can_get_translation_statistics()
    {
        // Create some test usage records
        \DB::table('translation_usage')->insert([
            [
                'source_language' => 'en',
                'target_language' => 'es',
                'character_count' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_language' => 'en',
                'target_language' => 'fr',
                'character_count' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->translationService->getUsageStatistics();

        $this->assertArrayHasKey('total_characters', $stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('languages', $stats);
        $this->assertEquals(250, $stats['total_characters']);
        $this->assertEquals(2, $stats['total_requests']);
    }
}