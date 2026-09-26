<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    public const LOCATIONS = [
        'header' => 'Header navigation',
        'footer_product' => 'Footer — Product',
        'footer_company' => 'Footer — Company',
        'footer_legal' => 'Footer — Legal',
    ];

    public function index()
    {
        $menus = Menu::all()->keyBy('location');

        return Inertia::render('Superadmin/Menus/Index', [
            'locations' => self::LOCATIONS,
            'menus' => collect(self::LOCATIONS)->map(fn ($label, $key) => collect($menus[$key]->items ?? [])
                ->map(fn ($i) => ['label' => $i['label'] ?? '', 'url' => $i['url'] ?? '#'])->values()->all())->all(),
            'suggestions' => [
                ...collect([
                    ['label' => 'Features', 'url' => '/features'],
                    ['label' => 'Pricing', 'url' => '/pricing'],
                    ['label' => 'About', 'url' => '/about'],
                    ['label' => 'Contact', 'url' => '/contact'],
                    ['label' => 'FAQ', 'url' => '/faq'],
                    ['label' => 'Blog', 'url' => '/blog'],
                    ['label' => 'Log in', 'url' => '/login'],
                    ['label' => 'Sign up', 'url' => '/register'],
                ])->all(),
                ...CmsPage::where('is_published', true)->orderBy('title')->get(['title', 'slug'])
                    ->map(fn ($p) => ['label' => $p->title, 'url' => '/page/' . $p->slug])->all(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*' => 'array|max:30',
            'menus.*.*.label' => 'required|string|max:60',
            // Relative, #anchor or http(s) only — menu links render on every public page, so a
            // javascript: URL here would be stored XSS for all visitors.
            'menus.*.*.url' => ['required', 'string', 'max:500', 'regex:#^(/(?!/)|\#|https?://)#i'],
        ], [
            'menus.*.*.url.regex' => 'Links must be relative (/pricing), an #anchor or start with http(s)://.',
        ]);

        foreach (array_keys(self::LOCATIONS) as $location) {
            $items = collect($validated['menus'][$location] ?? [])
                ->map(fn ($i) => ['label' => $i['label'], 'url' => $i['url']])
                ->values()->all();

            Menu::updateOrCreate(['location' => $location], ['items' => $items]);
        }

        Menu::clearCache();

        return back()->with('success', 'Menus saved.');
    }
}
