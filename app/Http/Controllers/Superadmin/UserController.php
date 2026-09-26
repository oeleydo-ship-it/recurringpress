<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['company.subscriptionPlan']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('company')) {
            $query->where('company_id', $request->company);
        }

        if ($request->filled('plan')) {
            $query->whereHas('company', fn ($q) => $q->where('subscription_plan_id', $request->plan));
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Superadmin/Users/Index', [
            'users' => $users->through(fn (User $u) => $this->row($u)),
            'filters' => $request->only('search', 'company', 'plan'),
            'plans' => SubscriptionPlan::ordered()->get(['id', 'name'])->mapWithKeys(fn ($p) => [$p->id => $p->name]),
        ]);
    }

    public function show(User $user)
    {
        $user->load(['company.subscriptionPlan', 'company.users']);
        $company = $user->company;

        return Inertia::render('Superadmin/Users/Show', [
            'user' => [
                ...$this->row($user),
                'phone' => $user->phone,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'currency' => $company->currency,
                'plan' => $company->subscriptionPlan?->name,
                'status' => $company->subscription_status,
                'subscription_ends_at' => $company->subscription_ends_at?->toDateString(),
                'onboarding_completed' => (bool) $company->onboarding_completed,
                'created_at' => $company->created_at?->toIso8601String(),
                'members' => $company->users->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'role' => $m->role]),
                'stats' => [
                    'invoices' => Invoice::withoutGlobalScopes()->where('company_id', $company->id)->count(),
                    'clients' => Client::withoutGlobalScopes()->where('company_id', $company->id)->count(),
                    'collected' => (float) Payment::withoutGlobalScopes()->where('company_id', $company->id)->where('status', 'completed')->sum('amount'),
                ],
            ] : null,
        ]);
    }

    public function edit(User $user)
    {
        $user->load('company');

        return Inertia::render('Superadmin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active !== false,
                'company' => $user->company ? ['id' => $user->company->id, 'name' => $user->company->name, 'plan_id' => $user->company->subscription_plan_id, 'status' => $user->company->subscription_status] : null,
            ],
            'roles' => User::roles(),
            'plans' => SubscriptionPlan::ordered()->get(['id', 'name', 'price'])
                ->mapWithKeys(fn ($p) => [$p->id => $p->name . ((float) $p->price > 0 ? ' ($' . number_format($p->price, 2) . ')' : ' (free)')]),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,filter', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:owner,admin,member',
            'is_active' => 'boolean',
            'plan_id' => ['nullable', 'integer', Rule::exists('subscription_plans', 'id')],
            'subscription_status' => 'nullable|in:active,trial,cancelled,expired',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        // Support can move a tenant between plans (e.g. to comp a customer).
        if ($user->company && $request->filled('plan_id')) {
            $user->company->update(array_filter([
                'subscription_plan_id' => $validated['plan_id'],
                'subscription_status' => $validated['subscription_status'] ?? null,
            ]));
        }

        return redirect()->route('superadmin.users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'owner') {
            $owners = User::where('company_id', $user->company_id)->where('role', 'owner')->count();
            if ($owners <= 1) {
                return back()->with('error', 'Cannot delete the last owner of a company.');
            }
        }

        $user->delete();

        return redirect()->route('superadmin.users.index')->with('success', 'User deleted.');
    }

    private function row(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'avatar_url' => $u->avatar_url,
            'is_active' => $u->is_active !== false,
            'company_id' => $u->company_id,
            'company_name' => $u->company?->name,
            'plan' => $u->company?->subscriptionPlan?->name,
            'subscription_status' => $u->company?->subscription_status,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
