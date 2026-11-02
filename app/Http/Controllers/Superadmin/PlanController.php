<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        return Inertia::render('Superadmin/Plans/Index', [
            'plans' => SubscriptionPlan::withCount('companies')->ordered()->get()->map(fn ($p) => [
                ...$this->payload($p),
                'companies_count' => $p->companies_count,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Superadmin/Plans/Form', ['plan' => null]);
    }

    public function store(Request $request)
    {
        SubscriptionPlan::create($this->validated($request));

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan created.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return Inertia::render('Superadmin/Plans/Form', ['plan' => $this->payload($plan)]);
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $plan->update($this->validated($request, $plan));

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        // Deleting a plan that tenants are on would orphan their subscription.
        if (Company::where('subscription_plan_id', $plan->id)->exists()) {
            return back()->with('error', 'Companies are subscribed to this plan. Deactivate it instead, or move them first.');
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan deleted.');
    }

    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($plan?->id)],
            'description' => 'nullable|string|max:1000',
            ...$this->priceRules($request),
            'trial_days' => 'required|integer|min:0|max:365',
            'max_invoices' => 'nullable|integer|min:0',
            'max_clients' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'max_products' => 'nullable|integer|min:0',
            'max_ai_requests' => 'nullable|integer|min:0|max:1000000',
            'features' => 'nullable|array|max:30',
            'features.*' => 'nullable|string|max:120',
            'stripe_enabled' => 'boolean',
            'custom_branding' => 'boolean',
            'white_label' => 'boolean',
            'custom_domain' => 'boolean',
            'subdomain' => 'boolean',
            'api_access' => 'boolean',
            'ai_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'order' => 'nullable|integer|min:0|max:1000',
        ], [
            'price_monthly.required' => 'Enter a monthly price or switch the monthly option off.',
            'price_yearly.required' => 'Enter a yearly price or switch the yearly option off.',
            'price_lifetime.required' => 'Enter a lifetime price or switch the lifetime option off.',
            'price_monthly.gt' => 'The monthly price must be more than zero. Switch every option off for a free plan.',
            'price_yearly.gt' => 'The yearly price must be more than zero. Switch every option off for a free plan.',
            'price_lifetime.gt' => 'The lifetime price must be more than zero. Switch every option off for a free plan.',
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['features'] = array_values(array_filter(array_map('trim', $validated['features'] ?? [])));
        $validated['order'] = $validated['order'] ?? 0;
        foreach (['max_invoices', 'max_clients', 'max_users', 'max_products', 'max_ai_requests'] as $limit) {
            $validated[$limit] = $validated[$limit] ?? null;
        }
        foreach (['monthly_enabled', 'yearly_enabled', 'lifetime_enabled', 'stripe_enabled', 'custom_branding', 'white_label', 'custom_domain', 'subdomain', 'api_access', 'ai_enabled', 'is_active', 'is_popular'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        return $validated;
    }

    /** A price is only required for options that are switched on; a switched-off price is kept for later. */
    private function priceRules(Request $request): array
    {
        $rules = [];
        foreach (SubscriptionPlan::PERIODS as $period) {
            $rules["{$period}_enabled"] = 'boolean';
            $rules["price_{$period}"] = $request->boolean("{$period}_enabled")
                ? 'required|numeric|gt:0|max:100000'
                : 'nullable|numeric|min:0|max:100000';
        }

        return $rules;
    }

    private function payload(SubscriptionPlan $p): array
    {
        return [
            ...$p->only('id', 'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'price_lifetime', 'monthly_enabled', 'yearly_enabled', 'lifetime_enabled', 'trial_days', 'max_invoices', 'max_clients', 'max_users', 'max_products', 'max_ai_requests', 'stripe_enabled', 'custom_branding', 'white_label', 'custom_domain', 'subdomain', 'api_access', 'ai_enabled', 'is_active', 'is_popular', 'order'),
            ...$p->pricingPayload(),
            'price_summary' => $p->priceSummary(),
            'features' => $p->features ?? [],
        ];
    }
}
