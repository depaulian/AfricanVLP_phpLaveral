<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Slider;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    /**
     * Display a listing of pages.
     */
    public function index()
    {
        $pages = Page::with(['creator', 'updater'])
            ->withCount(['sliders', 'pageSections'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new page.
     */
    public function create()
    {
        $templates = $this->getAvailableTemplates();
        $sectionTypes = PageSection::getSectionTypes();
        
        return view('admin.pages.create', compact('templates', 'sectionTypes'));
    }

    /**
     * Store a newly created page.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'template' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $page = Page::create([
                'title' => $request->title,
                'slug' => $request->slug,
                'content' => $request->content,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'status' => $request->status,
                'template' => $request->template,
                'settings' => $request->settings ?? [],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create sections if provided
            if ($request->has('sections')) {
                $this->createPageSections($page, $request->sections);
            }

            DB::commit();

            Log::info('Page created', ['page_id' => $page->id, 'user' => auth()->id()]);

            return redirect()->route('admin.pages.show', $page)
                ->with('success', 'Page created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create page', ['error' => $e->getMessage()]);
            
            return back()->withInput()
                ->with('error', 'Failed to create page: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified page.
     */
    public function show(Page $page)
    {
        $page->load(['creator', 'updater', 'sliders' => function ($query) {
            $query->ordered();
        }, 'pageSections' => function ($query) {
            $query->ordered();
        }]);

        return view('admin.pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Page $page)
    {
        $page->load(['sliders' => function ($query) {
            $query->ordered();
        }, 'pageSections' => function ($query) {
            $query->ordered();
        }]);

        $templates = $this->getAvailableTemplates();
        $sectionTypes = PageSection::getSectionTypes();
        
        return view('admin.pages.edit', compact('page', 'templates', 'sectionTypes'));
    }

    /**
     * Update the specified page.
     */
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'template' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $page->update([
                'title' => $request->title,
                'slug' => $request->slug,
                'content' => $request->content,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'status' => $request->status,
                'template' => $request->template,
                'settings' => $request->settings ?? [],
                'updated_by' => auth()->id(),
            ]);

            // Update sections if provided
            if ($request->has('sections')) {
                $this->updatePageSections($page, $request->sections);
            }

            DB::commit();

            Log::info('Page updated', ['page_id' => $page->id, 'user' => auth()->id()]);

            return redirect()->route('admin.pages.show', $page)
                ->with('success', 'Page updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update page', ['page_id' => $page->id, 'error' => $e->getMessage()]);
            
            return back()->withInput()
                ->with('error', 'Failed to update page: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified page.
     */
    public function destroy(Page $page)
    {
        try {
            // Prevent deletion of homepage
            if ($page->isHomepage()) {
                return back()->with('error', 'Cannot delete the homepage.');
            }

            $page->delete();

            Log::info('Page deleted', ['page_id' => $page->id, 'user' => auth()->id()]);

            return redirect()->route('admin.pages.index')
                ->with('success', 'Page deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete page', ['page_id' => $page->id, 'error' => $e->getMessage()]);
            
            return back()->with('error', 'Failed to delete page: ' . $e->getMessage());
        }
    }

    /**
     * Manage homepage content.
     */
    public function homepage()
    {
        $homepage = Page::homepage()->first();
        
        if (!$homepage) {
            // Create default homepage if it doesn't exist
            $homepage = Page::create([
                'title' => 'Homepage',
                'slug' => 'home',
                'content' => '',
                'status' => 'published',
                'template' => 'client.pages.home',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $homepage->load(['sliders' => function ($query) {
            $query->active()->ordered();
        }, 'pageSections' => function ($query) {
            $query->active()->ordered();
        }]);

        $sectionTypes = PageSection::getSectionTypes();

        return view('admin.pages.homepage', compact('homepage', 'sectionTypes'));
    }

    /**
     * Update homepage content.
     */
    public function updateHomepage(Request $request)
    {
        $homepage = Page::homepage()->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $homepage->update([
                'title' => $request->title,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'settings' => $request->settings ?? [],
                'updated_by' => auth()->id(),
            ]);

            // Update sections if provided
            if ($request->has('sections')) {
                $this->updatePageSections($homepage, $request->sections);
            }

            DB::commit();

            Log::info('Homepage updated', ['user' => auth()->id()]);

            return back()->with('success', 'Homepage updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update homepage', ['error' => $e->getMessage()]);
            
            return back()->withInput()
                ->with('error', 'Failed to update homepage: ' . $e->getMessage());
        }
    }

    /**
     * Generate slug from title.
     */
    public function generateSlug(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $slug = Str::slug($request->title);
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while (Page::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Get available templates.
     */
    private function getAvailableTemplates()
    {
        return [
            'client.pages.default' => 'Default Template',
            'client.pages.home' => 'Homepage Template',
            'client.pages.about' => 'About Page Template',
            'client.pages.contact' => 'Contact Page Template',
            'client.pages.full-width' => 'Full Width Template',
            'client.pages.sidebar' => 'Sidebar Template',
        ];
    }

    /**
     * Create page sections.
     */
    private function createPageSections(Page $page, array $sections)
    {
        foreach ($sections as $index => $sectionData) {
            PageSection::create([
                'page_id' => $page->id,
                'section_type' => $sectionData['type'],
                'title' => $sectionData['title'] ?? null,
                'subtitle' => $sectionData['subtitle'] ?? null,
                'content' => $sectionData['content'] ?? null,
                'image_url' => $sectionData['image_url'] ?? null,
                'settings' => $sectionData['settings'] ?? [],
                'position' => $index + 1,
                'status' => $sectionData['status'] ?? 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Update page sections.
     */
    private function updatePageSections(Page $page, array $sections)
    {
        // Delete existing sections
        $page->pageSections()->delete();
        
        // Create new sections
        $this->createPageSections($page, $sections);
    }
}