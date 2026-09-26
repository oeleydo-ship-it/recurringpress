<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::all()->keyBy('location');
        return view('superadmin.menus.index', compact('menus'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.location' => 'required|string',
            'menus.*.items' => 'nullable|array',
        ]);

        foreach ($validated['menus'] as $menuData) {
            Menu::updateOrCreate(
                ['location' => $menuData['location']],
                ['items' => $menuData['items'] ?? []]
            );
        }

        Menu::clearCache();

        return redirect()->route('superadmin.menus.index')->with('success', 'Menus updated successfully.');
    }
}
