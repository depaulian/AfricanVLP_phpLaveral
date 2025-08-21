<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Widget;
use App\Models\Organization;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WidgetController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display widgets dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Widget::with(['createdBy', 'organization'])
            ->orderBy('page')
            ->orderBy('position')
            ->orderBy('sort_order');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('page')) {
            $query->where('page', $request->page);
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('organization_id')) {
            if ($request->organization_id === 'global') {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $request->organization_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $widgets = $query->paginate(20);
        $organizations = Organization::orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'widgets' => $widgets,
                'organizations' => $organizations,
                'stats' => $this->getWidgetStats(),
            ]);
        }

        return view('admin.widgets.index', compact('widgets', 'organizations'));
    }

    /**
     * Show create widget form
     */
    public function create(): View
    {
        $organizations = Organization::orderBy('name')->get();
        $types = Widget::getAvailableTypes();
        $positions = Widget::getAvailablePositions();
        $pages = Widget::getAvailablePages();

        return view('admin.widgets.create', compact('organizations', 'types', 'positions', 'pages'));
    }

    /**
     * Store new widget
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:widgets,name',
            'title' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', Widget::getAvailableTypes()),
            'position' => 'required|in:' . implode(',', Widget::getAvailablePositions()),
            'page' => 'required|in:' . implode(',', Widget::getAvailablePages()),
            'content' => 'required|array',
            'settings' => 'sometimes|array',
            'visibility_rules' => 'sometimes|array',
            'cache_duration' => 'sometimes|integer|min:0|max:86400',
            'sort_order' => 'sometimes|integer|min:0',
            'organization_id' => 'sometimes|nullable|exists:organizations,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Validate content based on widget type
        $contentValidation = $this->validateWidgetContent($request->type, $request->content);
        if (!$contentValidation['valid']) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $contentValidation['message']], 422);
            }
            return back()->withErrors(['content' => $contentValidation['message']])->withInput();
        }

        DB::beginTransaction();
        try {
            // Set sort order if not provided
            if (!$request->filled('sort_order')) {
                $maxOrder = Widget::where('page', $request->page)
                    ->where('position', $request->position)
                    ->max('sort_order') ?? 0;
                $request->merge(['sort_order' => $maxOrder + 1]);
            }

            $widget = Widget::create([
                'name' => $request->name,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'position' => $request->position,
                'page' => $request->page,
                'content' => $request->content,
                'settings' => $request->settings ?? [],
                'visibility_rules' => $request->visibility_rules ?? [],
                'cache_duration' => $request->cache_duration ?? 3600,
                'sort_order' => $request->sort_order,
                'organization_id' => $request->organization_id,
                'is_active' => $request->boolean('is_active', true),
                'is_system' => false,
                'created_by' => auth()->id(),
            ]);

            // Clear cache for this page/position
            $this->clearWidgetCache($widget->page, $widget->position);

            // Log the creation
            $this->activityLogService->log(
                'create',
                $widget,
                auth()->user(),
                "Created widget: {$widget->name}"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Widget created successfully',
                    'widget' => $widget->load(['createdBy', 'organization'])
                ]);
            }

            return redirect()->route('admin.widgets.show', $widget)
                            ->with('success', 'Widget created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create widget'], 500);
            }
            
            return back()->with('error', 'Failed to create widget');
        }
    }

    /**
     * Show specific widget
     */
    public function show(Widget $widget): View|JsonResponse
    {
        $widget->load(['createdBy', 'organization']);

        if (request()->expectsJson()) {
            return response()->json(['widget' => $widget]);
        }

        return view('admin.widgets.show', compact('widget'));
    }

    /**
     * Show edit widget form
     */
    public function edit(Widget $widget): View
    {
        $widget->load(['createdBy', 'organization']);
        $organizations = Organization::orderBy('name')->get();
        $types = Widget::getAvailableTypes();
        $positions = Widget::getAvailablePositions();
        $pages = Widget::getAvailablePages();

        return view('admin.widgets.edit', compact('widget', 'organizations', 'types', 'positions', 'pages'));
    }

    /**
     * Update widget
     */
    public function update(Request $request, Widget $widget): JsonResponse|RedirectResponse
    {
        // Check if system widget
        if ($widget->is_system && !auth()->user()->hasRole('super_admin')) {
            $error = 'System widgets can only be modified by super administrators';
            if ($request->expectsJson()) {
                return response()->json(['error' => $error], 403);
            }
            return back()->with('error', $error);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:widgets,name,' . $widget->id,
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'type' => 'sometimes|in:' . implode(',', Widget::getAvailableTypes()),
            'position' => 'sometimes|in:' . implode(',', Widget::getAvailablePositions()),
            'page' => 'sometimes|in:' . implode(',', Widget::getAvailablePages()),
            'content' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'visibility_rules' => 'sometimes|array',
            'cache_duration' => 'sometimes|integer|min:0|max:86400',
            'sort_order' => 'sometimes|integer|min:0',
            'organization_id' => 'sometimes|nullable|exists:organizations,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Validate content if type or content changed
        if ($request->filled('content') || $request->filled('type')) {
            $type = $request->type ?? $widget->type;
            $content = $request->content ?? $widget->content;
            
            $contentValidation = $this->validateWidgetContent($type, $content);
            if (!$contentValidation['valid']) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => $contentValidation['message']], 422);
                }
                return back()->withErrors(['content' => $contentValidation['message']])->withInput();
            }
        }

        $oldData = $widget->toArray();
        $oldPage = $widget->page;
        $oldPosition = $widget->position;

        $widget->update($request->only([
            'name', 'title', 'description', 'type', 'position', 'page',
            'content', 'settings', 'visibility_rules', 'cache_duration',
            'sort_order', 'organization_id', 'is_active'
        ]));

        // Clear cache for old and new positions
        $this->clearWidgetCache($oldPage, $oldPosition);
        if ($widget->page !== $oldPage || $widget->position !== $oldPosition) {
            $this->clearWidgetCache($widget->page, $widget->position);
        }

        // Log the update
        $this->activityLogService->log(
            'update',
            $widget,
            auth()->user(),
            "Updated widget: {$widget->name}",
            ['old_data' => $oldData, 'new_data' => $widget->fresh()->toArray()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Widget updated successfully',
                'widget' => $widget->fresh()->load(['createdBy', 'organization'])
            ]);
        }

        return redirect()->route('admin.widgets.show', $widget)
                        ->with('success', 'Widget updated successfully');
    }

    /**
     * Delete widget
     */
    public function destroy(Widget $widget): JsonResponse|RedirectResponse
    {
        // Check if system widget
        if ($widget->is_system) {
            $error = 'System widgets cannot be deleted';
            if (request()->expectsJson()) {
                return response()->json(['error' => $error], 403);
            }
            return back()->with('error', $error);
        }

        try {
            $widgetName = $widget->name;
            $page = $widget->page;
            $position = $widget->position;

            $widget->delete();

            // Clear cache
            $this->clearWidgetCache($page, $position);

            // Log the deletion
            $this->activityLogService->log(
                'delete',
                $widget,
                auth()->user(),
                "Deleted widget: {$widgetName}"
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Widget deleted successfully']);
            }

            return redirect()->route('admin.widgets.index')
                            ->with('success', 'Widget deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete widget'], 500);
            }
            
            return back()->with('error', 'Failed to delete widget');
        }
    }

    /**
     * Duplicate widget
     */
    public function duplicate(Widget $widget): JsonResponse|RedirectResponse
    {
        try {
            $newWidget = $widget->replicate();
            $newWidget->name = $widget->name . '_copy_' . time();
            $newWidget->title = $widget->title . ' (Copy)';
            $newWidget->is_active = false;
            $newWidget->created_by = auth()->id();
            $newWidget->save();

            // Log the duplication
            $this->activityLogService->log(
                'create',
                $newWidget,
                auth()->user(),
                "Duplicated widget: {$widget->name} as {$newWidget->name}"
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Widget duplicated successfully',
                    'widget' => $newWidget->load(['createdBy', 'organization'])
                ]);
            }

            return redirect()->route('admin.widgets.edit', $newWidget)
                            ->with('success', 'Widget duplicated successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to duplicate widget'], 500);
            }
            
            return back()->with('error', 'Failed to duplicate widget');
        }
    }

    /**
     * Reorder widgets
     */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|exists:widgets,id',
            'widgets.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $updated = 0;
            $cacheKeys = [];

            foreach ($request->widgets as $widgetData) {
                $widget = Widget::find($widgetData['id']);
                if ($widget) {
                    $widget->update(['sort_order' => $widgetData['sort_order']]);
                    $cacheKeys[] = $widget->page . ':' . $widget->position;
                    $updated++;
                }
            }

            // Clear cache for affected positions
            foreach (array_unique($cacheKeys) as $cacheKey) {
                [$page, $position] = explode(':', $cacheKey);
                $this->clearWidgetCache($page, $position);
            }

            // Log the reorder
            $this->activityLogService->log(
                'update',
                null,
                auth()->user(),
                "Reordered {$updated} widgets"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully reordered {$updated} widgets"
                ]);
            }

            return back()->with('success', "Successfully reordered {$updated} widgets");

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to reorder widgets'], 500);
            }
            
            return back()->with('error', 'Failed to reorder widgets');
        }
    }

    /**
     * Bulk update widgets
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'widget_ids' => 'required|array',
            'widget_ids.*' => 'exists:widgets,id',
            'action' => 'required|in:activate,deactivate,delete,move',
            'value' => 'required_if:action,move',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $widgets = Widget::whereIn('id', $request->widget_ids)->get();
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($widgets as $widget) {
                // Check system widget restrictions
                if ($widget->is_system && in_array($request->action, ['delete'])) {
                    $errors[] = "Cannot {$request->action} system widget: {$widget->name}";
                    continue;
                }

                switch ($request->action) {
                    case 'activate':
                        $widget->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $widget->update(['is_active' => false]);
                        break;
                    case 'move':
                        [$newPage, $newPosition] = explode(':', $request->value);
                        $oldPage = $widget->page;
                        $oldPosition = $widget->position;
                        
                        $widget->update([
                            'page' => $newPage,
                            'position' => $newPosition,
                        ]);
                        
                        // Clear cache for old and new positions
                        $this->clearWidgetCache($oldPage, $oldPosition);
                        $this->clearWidgetCache($newPage, $newPosition);
                        break;
                    case 'delete':
                        $widget->delete();
                        break;
                }

                $this->activityLogService->log(
                    $request->action === 'delete' ? 'delete' : 'update',
                    $widget,
                    auth()->user(),
                    "Bulk {$request->action} on widget: {$widget->name}"
                );

                $updated++;
            }

            DB::commit();

            $message = "Successfully updated {$updated} widgets";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'updated' => $updated,
                    'errors' => $errors,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk update failed'], 500);
            }
            
            return back()->with('error', 'Bulk update failed');
        }
    }

    /**
     * Get widget statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getWidgetStats());
    }

    /**
     * Preview widget
     */
    public function preview(Widget $widget): JsonResponse
    {
        $renderedContent = $widget->getRenderedContent();

        return response()->json([
            'widget' => $widget,
            'rendered_content' => $renderedContent,
        ]);
    }

    /**
     * Clear widget cache
     */
    public function clearCache(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'sometimes|string',
            'position' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        if ($request->filled('page') && $request->filled('position')) {
            $this->clearWidgetCache($request->page, $request->position);
            $message = "Cache cleared for {$request->page}:{$request->position}";
        } else {
            Cache::tags(['widgets'])->flush();
            $message = "All widget cache cleared";
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
     * Export widgets as CSV
     */
    public function export(Request $request)
    {
        $query = Widget::with(['createdBy', 'organization']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('page')) {
            $query->where('page', $request->page);
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        $widgets = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="widgets_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($widgets) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Title', 'Type', 'Page', 'Position', 'Sort Order',
                'Status', 'System', 'Organization', 'Created By', 'Created At'
            ]);

            // CSV data
            foreach ($widgets as $widget) {
                fputcsv($file, [
                    $widget->id,
                    $widget->name,
                    $widget->title,
                    $widget->type,
                    $widget->page,
                    $widget->position,
                    $widget->sort_order,
                    $widget->is_active ? 'Active' : 'Inactive',
                    $widget->is_system ? 'Yes' : 'No',
                    $widget->organization->name ?? 'Global',
                    $widget->createdBy->name ?? '',
                    $widget->created->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Validate widget content based on type
     */
    protected function validateWidgetContent(string $type, array $content): array
    {
        switch ($type) {
            case 'html':
                if (!isset($content['html'])) {
                    return ['valid' => false, 'message' => 'HTML content is required'];
                }
                break;
            case 'text':
                if (!isset($content['text'])) {
                    return ['valid' => false, 'message' => 'Text content is required'];
                }
                break;
            case 'stats':
                if (!isset($content['stats']) || !is_array($content['stats'])) {
                    return ['valid' => false, 'message' => 'Stats data is required'];
                }
                break;
            case 'chart':
                if (!isset($content['chart'])) {
                    return ['valid' => false, 'message' => 'Chart data is required'];
                }
                break;
            case 'list':
                if (!isset($content['items']) || !is_array($content['items'])) {
                    return ['valid' => false, 'message' => 'List items are required'];
                }
                break;
            case 'feed':
                if (!isset($content['feed_url'])) {
                    return ['valid' => false, 'message' => 'Feed URL is required'];
                }
                break;
        }

        return ['valid' => true];
    }

    /**
     * Clear widget cache for specific page/position
     */
    protected function clearWidgetCache(string $page, string $position): void
    {
        $cacheKey = "widgets:{$page}:{$position}";
        Cache::forget($cacheKey);
        Cache::tags(['widgets', "page:{$page}", "position:{$position}"])->flush();
    }

    /**
     * Get widget statistics for dashboard
     */
    private function getWidgetStats(): array
    {
        $total = Widget::count();
        $active = Widget::where('is_active', true)->count();
        $inactive = Widget::where('is_active', false)->count();
        $system = Widget::where('is_system', true)->count();
        $custom = Widget::where('is_system', false)->count();
        
        $recentlyCreated = Widget::where('created', '>=', Carbon::now()->subDays(7))->count();

        // Type distribution
        $typeDistribution = Widget::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Page distribution
        $pageDistribution = Widget::selectRaw('page, COUNT(*) as count')
            ->groupBy('page')
            ->pluck('count', 'page')
            ->toArray();

        // Position distribution
        $positionDistribution = Widget::selectRaw('position, COUNT(*) as count')
            ->groupBy('position')
            ->pluck('count', 'position')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'system' => $system,
            'custom' => $custom,
            'recently_created' => $recentlyCreated,
            'type_distribution' => $typeDistribution,
            'page_distribution' => $pageDistribution,
            'position_distribution' => $positionDistribution,
            'activity_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }
}
