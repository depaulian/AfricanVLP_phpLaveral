<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    protected $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Display translation management interface
     */
    public function index()
    {
        $stats = $this->translationService->getStats();
        $supportedLanguages = $this->translationService->getSupportedLanguages();

        return view('admin.translations.index', compact('stats', 'supportedLanguages'));
    }

    /**
     * Translate text
     */
    public function translate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->translationService->translateText(
            $request->input('text'),
            $request->input('target_language'),
            $request->input('source_language')
        );

        return response()->json($result);
    }

    /**
     * Translate multiple texts
     */
    public function translateMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'texts' => 'required|array|max:50',
            'texts.*' => 'required|string|max:1000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->translationService->translateMultiple(
            $request->input('texts'),
            $request->input('target_language'),
            $request->input('source_language')
        );

        return response()->json($result);
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->translationService->detectLanguage($request->input('text'));

        return response()->json($result);
    }

    /**
     * Translate HTML content
     */
    public function translateHtml(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string|max:10000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->translationService->translateHtml(
            $request->input('html'),
            $request->input('target_language'),
            $request->input('source_language')
        );

        return response()->json($result);
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): JsonResponse
    {
        $languages = $this->translationService->getSupportedLanguages();

        return response()->json([
            'success' => true,
            'data' => $languages
        ]);
    }

    /**
     * Get translation statistics
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->translationService->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Clear translation cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $pattern = $request->input('pattern');
        $result = $this->translationService->clearCache($pattern);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Translation cache cleared successfully' : 'Failed to clear translation cache'
        ]);
    }
}