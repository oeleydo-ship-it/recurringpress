<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->paginate(20);
        return view('superadmin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('superadmin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_invoices' => 'nullable|integer|min:1',
            'max_clients' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_products' => 'nullable|integer|min:1',
            'stripe_enabled' => 'boolean',
            'custom_branding' => 'boolean',
            'api_access' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'order' => 'integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['stripe_enabled'] = $request->boolean('stripe_enabled');
        $validated['custom_branding'] = $request->boolean('custom_branding');
        $validated['api_access'] = $request->boolean('api_access');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popular'] = $request->boolean('is_popular');

        SubscriptionPlan::create($validated);

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_invoices' => 'nullable|integer|min:1',
            'max_clients' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_products' => 'nullable|integer|min:1',
            'stripe_enabled' => 'boolean',
            'custom_branding' => 'boolean',
            'api_access' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'order' => 'integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['stripe_enabled'] = $request->boolean('stripe_enabled');
        $validated['custom_branding'] = $request->boolean('custom_branding');
        $validated['api_access'] = $request->boolean('api_access');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popular'] = $request->boolean('is_popular');

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        $plan->delete();
        return redirect()->route('superadmin.plans.index')->with('success', 'Plan deleted successfully.');
    }
}
