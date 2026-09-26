<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = CmsPage::ordered()->paginate(20);
        return view('superadmin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('superadmin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages',
            'content' => 'nullable|string',
            'blocks' => 'nullable|json',
            'editor_mode' => 'in:classic,blocks',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'show_title' => 'boolean',
            'order' => 'integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['show_title'] = $request->has('show_title');
        $validated['editor_mode'] = $request->input('editor_mode', 'classic');
        
        if (!empty($validated['blocks'])) {
            $validated['blocks'] = json_decode($validated['blocks'], true);
        }

        CmsPage::create($validated);

        return redirect()->route('superadmin.pages.index')->with('success', 'Page created successfully.');
    }

    public function edit(CmsPage $page)
    {
        return view('superadmin.pages.edit', compact('page'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug,' . $page->id,
            'content' => 'nullable|string',
            'blocks' => 'nullable|json',
            'editor_mode' => 'in:classic,blocks',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'show_title' => 'boolean',
            'order' => 'integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['show_title'] = $request->has('show_title');
        $validated['editor_mode'] = $request->input('editor_mode', $page->editor_mode ?? 'classic');
        
        if (!empty($validated['blocks'])) {
            $validated['blocks'] = json_decode($validated['blocks'], true);
        }

        $page->update($validated);

        return redirect()->route('superadmin.pages.edit', $page)->with('success', 'Page updated successfully.');
    }

    public function destroy(CmsPage $page)
    {
        $page->delete();
        return redirect()->route('superadmin.pages.index')->with('success', 'Page deleted successfully.');
    }
}
