<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['company.subscriptionPlan']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by company
        if ($request->filled('company')) {
            $query->where('company_id', $request->company);
        }

        // Filter by subscription plan
        if ($request->filled('plan')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('subscription_plan_id', $request->plan);
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get();
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('superadmin.users.index', compact('users', 'companies', 'plans'));
    }

    public function show(User $user)
    {
        $user->load(['company.subscriptionPlan']);

        return view('superadmin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $user->load('company');

        return view('superadmin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:owner,admin,member',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        return redirect()->route('superadmin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting the last owner of a company
        if ($user->role === 'owner') {
            $ownerCount = User::where('company_id', $user->company_id)
                ->where('role', 'owner')
                ->count();

            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'Cannot delete the last owner of a company.');
            }
        }

        $user->delete();

        return redirect()->route('superadmin.users.index')->with('success', 'User deleted successfully.');
    }
}
