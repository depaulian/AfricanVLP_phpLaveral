<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\Page;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of sliders.
     */
    public function index()
    {
        $sliders = Slider::with(['page', 'creator', 'updater'])
            ->ordered()
            ->paginate(15);

        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * Show the form for creating a new slider.
     */
    public function create()
    {
        $pages = Page::where('status', 'published')->orderBy('title')->get();
        
        return view('admin.sliders.create', compact('pages'));
    }

    /**
     * Store a newly created slider.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'link_url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'page_id' => 'required|exists:pages,id',
            'position' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'show_overlay' => 'boolean',
            'text_position' => 'nullable|in:left,center,right',
            'animation_type' => 'nullable|in:fade,slide,zoom',
        ]);

        try {
            DB::beginTransaction();

            // Upload image
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'sliders',
                    [
                        'width' => 1920,
                        'height' => 800,
                        'crop' => 'fill',
                        'quality' => 85,
                        'format' => 'webp'
                    ]
                );
                $imageUrl = $uploadResult['secure_url'];
            }

            // Get next position if not provided
            $position = $request->input('position');
            if (is_null($position)) {
                $position = Slider::where('page_id', $request->input('page_id'))->max('position') + 1;
            }

            $slider = Slider::create([
                'title' => $request->input('title'),
                'subtitle' => $request->input('subtitle'),
                'description' => $request->input('description'),
                'image_url' => $imageUrl,
                'link_url' => $request->input('link_url'),
                'link_text' => $request->input('link_text'),
                'page_id' => $request->input('page_id'),
                'position' => $position,
                'status' => $request->input('status'),
                'show_overlay' => $request->boolean('show_overlay'),
                'text_position' => $request->input('text_position', 'left'),
                'animation_type' => $request->input('animation_type', 'fade'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            // Clear slider cache
            $this->clearSliderCache($slider->page_id);

            Log::info('Slider created', [
                'slider_id' => $slider->id,
                'title' => $slider->title,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create slider', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create slider. Please try again.');
        }
    }

    /**
     * Display the specified slider.
     */
    public function show(Slider $slider)
    {
        $slider->load(['page', 'creator', 'updater']);
        
        return view('admin.sliders.show', compact('slider'));
    }

    /**
     * Show the form for editing the specified slider.
     */
    public function edit(Slider $slider)
    {
        $pages = Page::where('status', 'published')->orderBy('title')->get();
        
        return view('admin.sliders.edit', compact('slider', 'pages'));
    }

    /**
     * Update the specified slider.
     */
    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link_url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'page_id' => 'required|exists:pages,id',
            'position' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'show_overlay' => 'boolean',
            'text_position' => 'nullable|in:left,center,right',
            'animation_type' => 'nullable|in:fade,slide,zoom',
        ]);

        try {
            DB::beginTransaction();

            $oldPageId = $slider->page_id;
            $imageUrl = $slider->image_url;

            // Upload new image if provided
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'sliders',
                    [
                        'width' => 1920,
                        'height' => 800,
                        'crop' => 'fill',
                        'quality' => 85,
                        'format' => 'webp'
                    ]
                );
                $imageUrl = $uploadResult['secure_url'];

                // Delete old image if it exists
                if ($slider->image_url) {
                    $this->fileUploadService->deleteFile($slider->image_url);
                }
            }

            $slider->update([
                'title' => $request->input('title'),
                'subtitle' => $request->input('subtitle'),
                'description' => $request->input('description'),
                'image_url' => $imageUrl,
                'link_url' => $request->input('link_url'),
                'link_text' => $request->input('link_text'),
                'page_id' => $request->input('page_id'),
                'position' => $request->input('position', $slider->position),
                'status' => $request->input('status'),
                'show_overlay' => $request->boolean('show_overlay'),
                'text_position' => $request->input('text_position', 'left'),
                'animation_type' => $request->input('animation_type', 'fade'),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            // Clear slider cache for both old and new pages
            $this->clearSliderCache($oldPageId);
            if ($oldPageId !== $slider->page_id) {
                $this->clearSliderCache($slider->page_id);
            }

            Log::info('Slider updated', [
                'slider_id' => $slider->id,
                'title' => $slider->title,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update slider', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update slider. Please try again.');
        }
    }

    /**
     * Remove the specified slider.
     */
    public function destroy(Slider $slider)
    {
        try {
            DB::beginTransaction();

            $pageId = $slider->page_id;
            $imageUrl = $slider->image_url;

            // Delete the slider
            $slider->delete();

            // Delete associated image
            if ($imageUrl) {
                $this->fileUploadService->deleteFile($imageUrl);
            }

            DB::commit();

            // Clear slider cache
            $this->clearSliderCache($pageId);

            Log::info('Slider deleted', [
                'slider_id' => $slider->id,
                'title' => $slider->title,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete slider', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Failed to delete slider. Please try again.');
        }
    }

    /**
     * Update slider positions via AJAX.
     */
    public function updatePositions(Request $request)
    {
        $request->validate([
            'sliders' => 'required|array',
            'sliders.*.id' => 'required|exists:sliders,id',
            'sliders.*.position' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $pageIds = [];
            foreach ($request->input('sliders') as $sliderData) {
                $slider = Slider::find($sliderData['id']);
                $slider->update([
                    'position' => $sliderData['position'],
                    'updated_by' => auth()->id(),
                ]);
                $pageIds[] = $slider->page_id;
            }

            DB::commit();

            // Clear cache for affected pages
            foreach (array_unique($pageIds) as $pageId) {
                $this->clearSliderCache($pageId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Slider positions updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update slider positions', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update slider positions.'
            ], 500);
        }
    }

    /**
     * Toggle slider status.
     */
    public function toggleStatus(Slider $slider)
    {
        try {
            $newStatus = $slider->status === 'active' ? 'inactive' : 'active';
            
            $slider->update([
                'status' => $newStatus,
                'updated_by' => auth()->id(),
            ]);

            // Clear slider cache
            $this->clearSliderCache($slider->page_id);

            Log::info('Slider status toggled', [
                'slider_id' => $slider->id,
                'new_status' => $newStatus,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "Slider {$newStatus} successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle slider status', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update slider status.'
            ], 500);
        }
    }

    /**
     * Get sliders for a specific page (AJAX).
     */
    public function getByPage(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id'
        ]);

        $sliders = Slider::where('page_id', $request->input('page_id'))
            ->ordered()
            ->get(['id', 'title', 'status', 'position', 'image_url']);

        return response()->json([
            'success' => true,
            'sliders' => $sliders
        ]);
    }

    /**
     * Duplicate a slider.
     */
    public function duplicate(Slider $slider)
    {
        try {
            DB::beginTransaction();

            $newSlider = $slider->replicate();
            $newSlider->title = $slider->title . ' (Copy)';
            $newSlider->position = Slider::where('page_id', $slider->page_id)->max('position') + 1;
            $newSlider->status = 'inactive';
            $newSlider->created_by = auth()->id();
            $newSlider->updated_by = auth()->id();
            $newSlider->save();

            DB::commit();

            // Clear slider cache
            $this->clearSliderCache($slider->page_id);

            Log::info('Slider duplicated', [
                'original_slider_id' => $slider->id,
                'new_slider_id' => $newSlider->id,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.sliders.edit', $newSlider)
                ->with('success', 'Slider duplicated successfully. Please review and update as needed.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate slider', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Failed to duplicate slider. Please try again.');
        }
    }

    /**
     * Clear slider cache for a specific page.
     */
    private function clearSliderCache(int $pageId): void
    {
        Cache::forget("sliders_page_{$pageId}");
        Cache::forget("active_sliders_page_{$pageId}");
        Cache::forget('homepage_sliders');
        Cache::tags(['sliders', 'pages'])->flush();
    }
}