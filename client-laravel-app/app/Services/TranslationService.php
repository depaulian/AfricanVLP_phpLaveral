<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected $translateClient;
    protected $config;
    protected $supportedLanguages;

    public function __construct()
    {
        $this->config = config('services.google_translate');
        
        if ($this->config['api_key']) {
            $this->translateClient = new TranslateClient([
                'key' => $this->config['api_key']
            ]);
        }

        $this->supportedLanguages = [
            'en' => 'English',
            'fr' => 'French',
            'ar' => 'Arabic',
            'pt' => 'Portuguese',
            'es' => 'Spanish',
            'sw' => 'Swahili',
            'am' => 'Amharic',
            'ha' => 'Hausa',
            'yo' => 'Yoruba',
            'ig' => 'Igbo'
        ];
    }

    /**
     * Translate text to target language
     *
     * @param string $text
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'message' => 'Google Translate API not configured',
                'translated_text' => $text,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage
            ];
        }

        // Check if translation is needed
        if ($sourceLanguage && $sourceLanguage === $targetLanguage) {
            return [
                'success' => true,
                'translated_text' => $text,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'cached' => false
            ];
        }

        // Create cache key
        $cacheKey = 'translation_' . md5($text . '_' . $targetLanguage . '_' . ($sourceLanguage ?? 'auto'));
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['cached' => true]);
        }

        try {
            $options = [
                'target' => $targetLanguage
            ];

            if ($sourceLanguage) {
                $options['source'] = $sourceLanguage;
            }

            $result = $this->translateClient->translate($text, $options);

            $response = [
                'success' => true,
                'translated_text' => $result['text'],
                'source_language' => $result['source'] ?? $sourceLanguage,
                'target_language' => $targetLanguage,
                'cached' => false
            ];

            // Cache the result for 24 hours
            Cache::put($cacheKey, $response, now()->addHours(24));

            return $response;

        } catch (\Exception $e) {
            Log::error('Translation failed: ' . $e->getMessage(), [
                'text' => substr($text, 0, 100),
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage
            ]);

            return [
                'success' => false,
                'message' => 'Translation failed: ' . $e->getMessage(),
                'translated_text' => $text,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'cached' => false
            ];
        }
    }

    /**
     * Translate multiple texts
     *
     * @param array $texts
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translateMultiple(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'message' => 'Google Translate API not configured',
                'translations' => array_map(function($text) use ($targetLanguage, $sourceLanguage) {
                    return [
                        'original' => $text,
                        'translated' => $text,
                        'source_language' => $sourceLanguage,
                        'target_language' => $targetLanguage
                    ];
                }, $texts)
            ];
        }

        $translations = [];
        $errors = [];

        foreach ($texts as $index => $text) {
            $result = $this->translateText($text, $targetLanguage, $sourceLanguage);
            
            if ($result['success']) {
                $translations[] = [
                    'original' => $text,
                    'translated' => $result['translated_text'],
                    'source_language' => $result['source_language'],
                    'target_language' => $result['target_language'],
                    'cached' => $result['cached']
                ];
            } else {
                $errors[] = [
                    'index' => $index,
                    'text' => $text,
                    'error' => $result['message']
                ];
                
                // Add failed translation with original text
                $translations[] = [
                    'original' => $text,
                    'translated' => $text,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'error' => $result['message']
                ];
            }
        }

        return [
            'success' => count($errors) === 0,
            'translations' => $translations,
            'errors' => $errors,
            'total_translated' => count($translations),
            'total_errors' => count($errors)
        ];
    }

    /**
     * Detect language of text
     *
     * @param string $text
     * @return array
     */
    public function detectLanguage(string $text): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'message' => 'Google Translate API not configured',
                'language' => 'en',
                'confidence' => 0
            ];
        }

        try {
            $result = $this->translateClient->detectLanguage($text);

            return [
                'success' => true,
                'language' => $result['languageCode'],
                'confidence' => $result['confidence'],
                'language_name' => $this->getLanguageName($result['languageCode'])
            ];

        } catch (\Exception $e) {
            Log::error('Language detection failed: ' . $e->getMessage(), [
                'text' => substr($text, 0, 100)
            ]);

            return [
                'success' => false,
                'message' => 'Language detection failed: ' . $e->getMessage(),
                'language' => 'en',
                'confidence' => 0
            ];
        }
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Get language name from code
     *
     * @param string $languageCode
     * @return string
     */
    public function getLanguageName(string $languageCode): string
    {
        return $this->supportedLanguages[$languageCode] ?? $languageCode;
    }

    /**
     * Check if language is supported
     *
     * @param string $languageCode
     * @return bool
     */
    public function isLanguageSupported(string $languageCode): bool
    {
        return array_key_exists($languageCode, $this->supportedLanguages);
    }

    /**
     * Translate content with HTML preservation
     *
     * @param string $html
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translateHtml(string $html, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'message' => 'Google Translate API not configured',
                'translated_html' => $html
            ];
        }

        try {
            $options = [
                'target' => $targetLanguage,
                'format' => 'html'
            ];

            if ($sourceLanguage) {
                $options['source'] = $sourceLanguage;
            }

            $result = $this->translateClient->translate($html, $options);

            return [
                'success' => true,
                'translated_html' => $result['text'],
                'source_language' => $result['source'] ?? $sourceLanguage,
                'target_language' => $targetLanguage
            ];

        } catch (\Exception $e) {
            Log::error('HTML translation failed: ' . $e->getMessage(), [
                'html' => substr($html, 0, 200),
                'target_language' => $targetLanguage
            ]);

            return [
                'success' => false,
                'message' => 'HTML translation failed: ' . $e->getMessage(),
                'translated_html' => $html
            ];
        }
    }

    /**
     * Clear translation cache
     *
     * @param string|null $pattern
     * @return bool
     */
    public function clearCache(?string $pattern = null): bool
    {
        try {
            if ($pattern) {
                // Clear specific pattern
                $keys = Cache::getRedis()->keys("*translation_*{$pattern}*");
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // Clear all translation cache
                $keys = Cache::getRedis()->keys('*translation_*');
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear translation cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get translation statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $keys = Cache::getRedis()->keys('*translation_*');
            $totalCached = count($keys);

            return [
                'total_cached_translations' => $totalCached,
                'supported_languages' => count($this->supportedLanguages),
                'api_configured' => !is_null($this->translateClient)
            ];
        } catch (\Exception $e) {
            return [
                'total_cached_translations' => 0,
                'supported_languages' => count($this->supportedLanguages),
                'api_configured' => !is_null($this->translateClient)
            ];
        }
    }
}