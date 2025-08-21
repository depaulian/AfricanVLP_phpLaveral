<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class TranslationManagementController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display translations dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Translation::with(['createdBy'])
            ->orderBy('locale')
            ->orderBy('group')
            ->orderBy('key');

        // Apply filters
        if ($request->filled('locale')) {
            $query->where('locale', $request->locale);
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        if ($request->filled('namespace')) {
            $query->where('namespace', $request->namespace);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'needs_review':
                    $query->needsReview();
                    break;
                case 'missing':
                    $query->missing();
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%")
                  ->orWhere('group', 'like', "%{$search}%");
            });
        }

        $translations = $query->paginate(50);
        
        // Get available options for filters
        $locales = Translation::distinct('locale')->pluck('locale')->sort();
        $groups = Translation::distinct('group')->whereNotNull('group')->pluck('group')->sort();
        $namespaces = Translation::distinct('namespace')->whereNotNull('namespace')->pluck('namespace')->sort();

        if ($request->expectsJson()) {
            return response()->json([
                'translations' => $translations,
                'locales' => $locales,
                'groups' => $groups,
                'namespaces' => $namespaces,
                'stats' => $this->getTranslationStats(),
            ]);
        }

        return view('admin.translations.index', compact('translations', 'locales', 'groups', 'namespaces'));
    }

    /**
     * Show create translation form
     */
    public function create(): View
    {
        $locales = Translation::getSupportedLocales();
        $groups = Translation::getTranslationGroups();

        return view('admin.translations.create', compact('locales', 'groups'));
    }

    /**
     * Store new translation
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'locale' => 'required|string|size:2|in:' . implode(',', Translation::getSupportedLocales()),
            'value' => 'required|string',
            'group' => 'sometimes|nullable|string|max:100',
            'namespace' => 'sometimes|nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate key in same locale/group/namespace
        $validator->after(function ($validator) use ($request) {
            $existing = Translation::where('key', $request->key)
                ->where('locale', $request->locale)
                ->where('group', $request->group)
                ->where('namespace', $request->namespace)
                ->first();

            if ($existing) {
                $validator->errors()->add('key', 'Translation key already exists for this locale/group/namespace combination.');
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $translation = Translation::create([
                'key' => $request->key,
                'locale' => $request->locale,
                'value' => $request->value,
                'group' => $request->group,
                'namespace' => $request->namespace,
                'is_active' => $request->boolean('is_active', true),
                'is_system' => false,
                'created_by' => auth()->id(),
            ]);

            // Clear translation cache
            $this->clearTranslationCache($translation->locale);

            // Log the creation
            $this->activityLogService->log(
                'create',
                $translation,
                auth()->user(),
                "Created translation: {$translation->full_key} ({$translation->locale})"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Translation created successfully',
                    'translation' => $translation->load('createdBy')
                ]);
            }

            return redirect()->route('admin.translations.show', $translation)
                            ->with('success', 'Translation created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create translation'], 500);
            }
            
            return back()->with('error', 'Failed to create translation');
        }
    }

    /**
     * Show specific translation
     */
    public function show(Translation $translation): View|JsonResponse
    {
        $translation->load('createdBy');

        // Get related translations (same key, different locales)
        $relatedTranslations = Translation::where('key', $translation->key)
            ->where('group', $translation->group)
            ->where('namespace', $translation->namespace)
            ->where('id', '!=', $translation->id)
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'translation' => $translation,
                'related_translations' => $relatedTranslations,
            ]);
        }

        return view('admin.translations.show', compact('translation', 'relatedTranslations'));
    }

    /**
     * Show edit translation form
     */
    public function edit(Translation $translation): View
    {
        $translation->load('createdBy');
        $locales = Translation::getSupportedLocales();
        $groups = Translation::getTranslationGroups();

        return view('admin.translations.edit', compact('translation', 'locales', 'groups'));
    }

    /**
     * Update translation
     */
    public function update(Request $request, Translation $translation): JsonResponse|RedirectResponse
    {
        // Check if system translation
        if ($translation->is_system && !auth()->user()->hasRole('super_admin')) {
            $error = 'System translations can only be modified by super administrators';
            if ($request->expectsJson()) {
                return response()->json(['error' => $error], 403);
            }
            return back()->with('error', $error);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|string|max:255',
            'locale' => 'sometimes|string|size:2|in:' . implode(',', Translation::getSupportedLocales()),
            'value' => 'sometimes|string',
            'group' => 'sometimes|nullable|string|max:100',
            'namespace' => 'sometimes|nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check for duplicate key if key/locale/group/namespace changed
        if ($request->filled('key') || $request->filled('locale') || $request->filled('group') || $request->filled('namespace')) {
            $validator->after(function ($validator) use ($request, $translation) {
                $existing = Translation::where('key', $request->get('key', $translation->key))
                    ->where('locale', $request->get('locale', $translation->locale))
                    ->where('group', $request->get('group', $translation->group))
                    ->where('namespace', $request->get('namespace', $translation->namespace))
                    ->where('id', '!=', $translation->id)
                    ->first();

                if ($existing) {
                    $validator->errors()->add('key', 'Translation key already exists for this locale/group/namespace combination.');
                }
            });
        }

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldData = $translation->toArray();
        $oldLocale = $translation->locale;

        $translation->update($request->only([
            'key', 'locale', 'value', 'group', 'namespace', 'is_active'
        ]));

        // Clear translation cache for old and new locales
        $this->clearTranslationCache($oldLocale);
        if ($translation->locale !== $oldLocale) {
            $this->clearTranslationCache($translation->locale);
        }

        // Log the update
        $this->activityLogService->log(
            'update',
            $translation,
            auth()->user(),
            "Updated translation: {$translation->full_key} ({$translation->locale})",
            ['old_data' => $oldData, 'new_data' => $translation->fresh()->toArray()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Translation updated successfully',
                'translation' => $translation->fresh()->load('createdBy')
            ]);
        }

        return redirect()->route('admin.translations.show', $translation)
                        ->with('success', 'Translation updated successfully');
    }

    /**
     * Delete translation
     */
    public function destroy(Translation $translation): JsonResponse|RedirectResponse
    {
        // Check if system translation
        if ($translation->is_system) {
            $error = 'System translations cannot be deleted';
            if (request()->expectsJson()) {
                return response()->json(['error' => $error], 403);
            }
            return back()->with('error', $error);
        }

        try {
            $fullKey = $translation->full_key;
            $locale = $translation->locale;

            $translation->delete();

            // Clear translation cache
            $this->clearTranslationCache($locale);

            // Log the deletion
            $this->activityLogService->log(
                'delete',
                $translation,
                auth()->user(),
                "Deleted translation: {$fullKey} ({$locale})"
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Translation deleted successfully']);
            }

            return redirect()->route('admin.translations.index')
                            ->with('success', 'Translation deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete translation'], 500);
            }
            
            return back()->with('error', 'Failed to delete translation');
        }
    }

    /**
     * Bulk import translations from file
     */
    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv,xlsx',
            'locale' => 'required|string|size:2|in:' . implode(',', Translation::getSupportedLocales()),
            'group' => 'sometimes|nullable|string|max:100',
            'namespace' => 'sometimes|nullable|string|max:100',
            'overwrite' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $locale = $request->locale;
            $group = $request->group;
            $namespace = $request->namespace;
            $overwrite = $request->boolean('overwrite', false);

            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Parse file based on extension
            $extension = $file->getClientOriginalExtension();
            $data = $this->parseImportFile($file, $extension);

            foreach ($data as $key => $value) {
                // Check if translation already exists
                $existing = Translation::where('key', $key)
                    ->where('locale', $locale)
                    ->where('group', $group)
                    ->where('namespace', $namespace)
                    ->first();

                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }

                try {
                    if ($existing) {
                        $existing->update(['value' => $value]);
                    } else {
                        Translation::create([
                            'key' => $key,
                            'locale' => $locale,
                            'value' => $value,
                            'group' => $group,
                            'namespace' => $namespace,
                            'is_active' => true,
                            'is_system' => false,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to import key '{$key}': " . $e->getMessage();
                }
            }

            // Clear translation cache
            $this->clearTranslationCache($locale);

            // Log the import
            $this->activityLogService->log(
                'import',
                null,
                auth()->user(),
                "Imported {$imported} translations for locale {$locale}"
            );

            DB::commit();

            $message = "Import completed. {$imported} translations imported";
            if ($skipped > 0) {
                $message .= ", {$skipped} skipped";
            }
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', array_slice($errors, 0, 3));
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export translations to file
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'sometimes|string|size:2|in:' . implode(',', Translation::getSupportedLocales()),
            'group' => 'sometimes|string',
            'namespace' => 'sometimes|string',
            'format' => 'required|in:json,csv,php',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $query = Translation::query();

        if ($request->filled('locale')) {
            $query->where('locale', $request->locale);
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        if ($request->filled('namespace')) {
            $query->where('namespace', $request->namespace);
        }

        $translations = $query->get();

        $filename = 'translations_' . ($request->locale ?? 'all') . '_' . date('Y-m-d') . '.' . $request->format;

        switch ($request->format) {
            case 'json':
                return $this->exportAsJson($translations, $filename);
            case 'csv':
                return $this->exportAsCsv($translations, $filename);
            case 'php':
                return $this->exportAsPhp($translations, $filename);
        }
    }

    /**
     * Sync translations with Laravel language files
     */
    public function sync(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $synced = 0;
            $locales = Translation::getSupportedLocales();

            foreach ($locales as $locale) {
                $langPath = resource_path("lang/{$locale}");
                
                if (!File::exists($langPath)) {
                    continue;
                }

                $files = File::allFiles($langPath);
                
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $group = $file->getFilenameWithoutExtension();
                        $translations = include $file->getPathname();
                        
                        if (is_array($translations)) {
                            $synced += $this->syncTranslationsArray($translations, $locale, $group);
                        }
                    }
                }
            }

            // Log the sync
            $this->activityLogService->log(
                'sync',
                null,
                auth()->user(),
                "Synced {$synced} translations from language files"
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Synced {$synced} translations successfully"
                ]);
            }

            return back()->with('success', "Synced {$synced} translations successfully");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sync failed: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Get translation statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getTranslationStats());
    }

    /**
     * Get locale progress
     */
    public function localeProgress(Request $request): JsonResponse
    {
        $locales = $request->get('locales', Translation::getSupportedLocales());
        $progress = [];

        foreach ($locales as $locale) {
            $progress[$locale] = Translation::getLocaleProgress($locale);
        }

        return response()->json($progress);
    }

    /**
     * Clear translation cache
     */
    public function clearCache(Request $request): JsonResponse|RedirectResponse
    {
        $locale = $request->get('locale');

        if ($locale) {
            $this->clearTranslationCache($locale);
            $message = "Translation cache cleared for locale: {$locale}";
        } else {
            // Clear all translation cache
            Artisan::call('cache:clear');
            $message = "All translation cache cleared";
        }

        // Log the cache clear
        $this->activityLogService->log(
            'system',
            null,
            auth()->user(),
            $message
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Parse import file based on format
     */
    protected function parseImportFile($file, string $extension): array
    {
        $content = file_get_contents($file->getPathname());

        switch ($extension) {
            case 'json':
                return json_decode($content, true) ?? [];
            case 'csv':
                return $this->parseCsvContent($content);
            default:
                throw new \InvalidArgumentException("Unsupported file format: {$extension}");
        }
    }

    /**
     * Parse CSV content
     */
    protected function parseCsvContent(string $content): array
    {
        $lines = str_getcsv($content, "\n");
        $data = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) >= 2) {
                $data[$row[0]] = $row[1];
            }
        }

        return $data;
    }

    /**
     * Export translations as JSON
     */
    protected function exportAsJson($translations, string $filename)
    {
        $data = [];
        foreach ($translations as $translation) {
            $data[$translation->key] = $translation->value;
        }

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export translations as CSV
     */
    protected function exportAsCsv($translations, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($translations) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Key', 'Value', 'Locale', 'Group', 'Namespace']);

            foreach ($translations as $translation) {
                fputcsv($file, [
                    $translation->key,
                    $translation->value,
                    $translation->locale,
                    $translation->group,
                    $translation->namespace,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export translations as PHP array
     */
    protected function exportAsPhp($translations, string $filename)
    {
        $data = [];
        foreach ($translations as $translation) {
            $data[$translation->key] = $translation->value;
        }

        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";

        return response($content)
            ->header('Content-Type', 'application/x-php')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Sync translations array recursively
     */
    protected function syncTranslationsArray(array $translations, string $locale, string $group, string $prefix = ''): int
    {
        $synced = 0;

        foreach ($translations as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $synced += $this->syncTranslationsArray($value, $locale, $group, $fullKey);
            } else {
                $existing = Translation::where('key', $fullKey)
                    ->where('locale', $locale)
                    ->where('group', $group)
                    ->first();

                if (!$existing) {
                    Translation::create([
                        'key' => $fullKey,
                        'locale' => $locale,
                        'value' => $value,
                        'group' => $group,
                        'is_active' => true,
                        'is_system' => true,
                        'created_by' => auth()->id(),
                    ]);
                    $synced++;
                }
            }
        }

        return $synced;
    }

    /**
     * Clear translation cache for specific locale
     */
    protected function clearTranslationCache(string $locale): void
    {
        // Clear Laravel's translation cache
        Artisan::call('cache:forget', ['key' => "translations.{$locale}"]);
        
        // Clear any custom translation cache
        cache()->forget("translations:{$locale}");
        cache()->tags(['translations', "locale:{$locale}"])->flush();
    }

    /**
     * Get translation statistics for dashboard
     */
    private function getTranslationStats(): array
    {
        $total = Translation::count();
        $active = Translation::where('is_active', true)->count();
        $needsReview = Translation::whereRaw('1=1')->get()->filter(fn($t) => $t->needsReview())->count();
        $missing = Translation::missing()->count();

        $localeStats = [];
        foreach (Translation::getSupportedLocales() as $locale) {
            $localeStats[$locale] = Translation::getLocaleProgress($locale);
        }

        // Group distribution
        $groupDistribution = Translation::selectRaw('`group`, COUNT(*) as count')
            ->whereNotNull('group')
            ->groupBy('group')
            ->pluck('count', 'group')
            ->toArray();

        // Recent activity
        $recentTranslations = Translation::where('created', '>=', Carbon::now()->subDays(7))->count();

        return [
            'total' => $total,
            'active' => $active,
            'needs_review' => $needsReview,
            'missing' => $missing,
            'locale_stats' => $localeStats,
            'group_distribution' => $groupDistribution,
            'recent_translations' => $recentTranslations,
            'completion_rate' => $total > 0 ? round((($total - $missing) / $total) * 100, 2) : 0,
        ];
    }
}
