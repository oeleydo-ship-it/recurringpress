<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $company = auth()->user()->company;
        
        if ($company->hasReachedLimit('users')) {
            return redirect()->route('users.index')
                ->with('error', 'You have reached your user limit. Please upgrade your subscription plan to add more team members.');
        }
        
        $roles = User::roles();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        
        if ($company->hasReachedLimit('users')) {
            return back()->with('error', 'You have reached the user limit for your current plan. Please upgrade to add more team members.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:admin,member',
            'phone' => 'nullable|string|max:50',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'company_id' => auth()->user()->company_id,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Team member added successfully.');
    }

    public function edit(User $user)
    {
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $roles = User::roles();
        unset($roles['owner']);

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,member',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if ($user->isOwner()) {
            unset($validated['role']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'Team member updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        if ($user->isOwner()) {
            return back()->with('error', 'Cannot delete the company owner.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Team member removed successfully.');
    }

    public function profile()
    {
        return view('users.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
