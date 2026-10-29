<?php

namespace App\Http\Controllers;

use App\Mail\TeamInviteMail;
use App\Models\User;
use App\Services\TenantNotifier;
use App\Support\PasswordResetLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $me = auth()->user();
        $users = User::where('company_id', $me->company_id)->orderByRaw("FIELD(role, 'owner', 'admin', 'member')")->orderBy('name')->paginate(25);
        $company = $me->company;
        $plan = $company->subscriptionPlan;

        return Inertia::render('Users/Index', [
            'users' => $users->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'avatar_url' => $u->avatar_url,
                'is_active' => $u->is_active !== false,
                'is_me' => $u->id === $me->id,
                'can_manage' => $this->canManage($me, $u),
                'joined' => $u->created_at?->toDateString(),
            ]),
            'roles' => User::roles(),
            'limit' => ['used' => $company->users()->count(), 'max' => $plan?->max_users],
        ]);
    }

    public function create()
    {
        if (auth()->user()->company->hasReachedLimit('users')) {
            return redirect()->route('users.index')
                ->with('error', 'You have reached your user limit. Please upgrade your subscription plan to add more team members.');
        }

        return Inertia::render('Users/Form', ['member' => null, 'roles' => $this->assignableRoles()]);
    }

    public function store(Request $request)
    {
        $me = auth()->user();
        $company = $me->company;

        if ($company->hasReachedLimit('users')) {
            return back()->with('error', 'You have reached the user limit for your current plan. Please upgrade to add more team members.');
        }

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,filter|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_keys($this->assignableRoles()))],
            'phone' => 'nullable|string|max:50',
            'send_invite' => 'boolean',
            // Only required when the admin sets the password instead of inviting.
            'password' => ['exclude_if:send_invite,true', 'required', 'confirmed', Password::defaults()],
        ]);

        $invite = $request->boolean('send_invite', true);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $invite ? Str::password(40) : $validated['password'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'company_id' => $me->company_id,
        ]);
        $user->markEmailAsVerified();

        if ($invite) {
            Mail::to($user->email)->queue(new TeamInviteMail($user, $me->name, $company->name, PasswordResetLink::for($user)));
        }

        TenantNotifier::notify(
            $company,
            'team_member_added',
            "Team member added: {$user->name}",
            $user->email . ' joined as ' . (User::roles()[$user->role] ?? $user->role) . TenantNotifier::byActor() . '.',
            TenantNotifier::path('users.index'),
            TenantNotifier::AUDIENCE_ADMINS,
        );

        return redirect()->route('users.index')
            ->with('success', $invite ? "Invitation sent to {$user->email}." : 'Team member added.');
    }

    public function edit(User $user)
    {
        $this->authorizeManage($user);

        return Inertia::render('Users/Form', [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active !== false,
                'is_owner' => $user->isOwner(),
                'is_me' => $user->id === auth()->id(),
            ],
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManage($user);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,filter', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys($this->assignableRoles()))],
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        // The owner's role is fixed, and nobody can deactivate or demote themselves.
        if ($user->isOwner()) {
            unset($validated['role']);
        }
        if ($user->id === auth()->id()) {
            unset($validated['is_active'], $validated['role']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Team member updated.');
    }

    public function destroy(User $user)
    {
        $this->authorizeManage($user);

        if ($user->isOwner()) {
            return back()->with('error', 'Cannot delete the company owner.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $user->delete();

        TenantNotifier::notify(
            auth()->user()->company_id,
            'team_member_removed',
            "Team member removed: {$user->name}",
            $user->email . ' was removed' . TenantNotifier::byActor() . '.',
            TenantNotifier::path('users.index'),
            TenantNotifier::AUDIENCE_ADMINS,
        );

        return redirect()->route('users.index')->with('success', 'Team member removed.');
    }

    public function profile()
    {
        $user = auth()->user();

        return Inertia::render('Users/Profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $emailChanging = $request->input('email') !== Str::lower($user->email);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,filter', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // Changing the login email requires the password: otherwise a hijacked session
            // could redirect password resets to the attacker's inbox (account takeover).
            'current_password' => [Rule::requiredIf($emailChanging), 'nullable', 'current_password'],
        ]);
        unset($validated['current_password']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();
        // Rotating the remember token signs out "remember me" sessions on other devices.
        $user->forceFill([
            'password' => $request->password,
            'remember_token' => Str::random(60),
        ])->save();

        Auth::login($user); // refresh this session's password hash
        $request->session()->regenerate();

        return back()->with('success', 'Password changed.');
    }

    /* ------------------------------------------------------------------ */

    /**
     * Owners manage everyone; admins manage members (and themselves) only. Previously any
     * admin could edit the owner's email and then reset the owner's password.
     */
    private function canManage(User $actor, User $target): bool
    {
        if ($target->company_id !== $actor->company_id) {
            return false;
        }
        if ($actor->isOwner() || $actor->id === $target->id) {
            return true;
        }

        return $actor->role === User::ROLE_ADMIN && $target->role === User::ROLE_MEMBER;
    }

    private function authorizeManage(User $user): void
    {
        abort_unless($this->canManage(auth()->user(), $user), 403, 'You cannot manage this team member.');
    }

    private function assignableRoles(): array
    {
        $roles = User::roles();
        unset($roles[User::ROLE_OWNER]);

        // Only the owner can create or promote admins.
        if (! auth()->user()->isOwner()) {
            unset($roles[User::ROLE_ADMIN]);
        }

        return $roles;
    }
}
