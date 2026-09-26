<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PageController extends Controller
{
    public function index()
    {
        return Inertia::render('Superadmin/Pages/Index', [
            'pages' => CmsPage::ordered()->get()->map(fn (CmsPage $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'editor_mode' => $p->editor_mode ?? 'classic',
                'is_published' => (bool) $p->is_published,
                'order' => $p->order,
                'updated_at' => $p->updated_at?->toIso8601String(),
                'url' => route('page.show', $p->slug),
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Superadmin/Pages/Form', ['page' => null]);
    }

    public function store(Request $request)
    {
        $page = CmsPage::create($this->validated($request));

        return redirect()->route('superadmin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(CmsPage $page)
    {
        return Inertia::render('Superadmin/Pages/Form', [
            'page' => [
                ...$page->only('id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'order'),
                'blocks' => $page->blocks ?? [],
                'editor_mode' => $page->editor_mode ?? 'classic',
                'is_published' => (bool) $page->is_published,
                'show_title' => $page->show_title === null ? true : (bool) $page->show_title,
                'url' => route('page.show', $page->slug),
            ],
        ]);
    }

    public function update(Request $request, CmsPage $page)
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('superadmin.pages.edit', $page)->with('success', 'Page saved.');
    }

    public function destroy(CmsPage $page)
    {
        $page->delete();

        return redirect()->route('superadmin.pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'content' => 'nullable|string|max:500000',
            'blocks' => 'nullable|array|max:200',
            'editor_mode' => 'required|in:classic,blocks',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'show_title' => 'boolean',
            'order' => 'nullable|integer|min:0|max:1000',
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');
        // Previously $request->has('show_title') — true even when the box was unticked.
        $validated['show_title'] = $request->boolean('show_title');
        $validated['order'] = $validated['order'] ?? 0;
        $validated['blocks'] = $validated['blocks'] ?? [];

        return $validated;
    }
}
