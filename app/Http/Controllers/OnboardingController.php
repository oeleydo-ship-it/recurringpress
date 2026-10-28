<?php

namespace App\Http\Controllers;

use App\Mail\TeamInviteMail;
use App\Models\CompanySetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PasswordResetLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Total number of onboarding steps
     */
    const TOTAL_STEPS = 7;

    /**
     * Welcome screen shown right after registration / email verification.
     */
    public function welcome()
    {
        $user = auth()->user();
        $company = $user->company;

        if ($company->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Welcome', [
            'userName' => $user->name,
            'companyName' => $company->name,
            'totalSteps' => self::TOTAL_STEPS,
            'resumeStep' => (int) $company->onboarding_step,
        ]);
    }

    /**
     * Setup wizard.
     */
    public function setup()
    {
        $user = auth()->user();
        $company = $user->company;

        if ($company->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        return Inertia::render('Onboarding/Setup', [
            'company' => [
                'name' => $company->name,
                'logo_url' => $company->logo_url,
                'address' => $company->address,
                'city' => $company->city,
                'state' => $company->state,
                'postal_code' => $company->postal_code,
                'country' => $company->country,
                'phone' => $company->phone,
                'tax_number' => $company->tax_number,
                'currency' => $company->currency,
            ],
            'settings' => [
                'accept_bank_transfer' => (bool) $settings->accept_bank_transfer,
                'accept_cash' => (bool) $settings->accept_cash,
                'accept_stripe' => (bool) $settings->accept_stripe,
                'payment_gateway_mode' => $settings->payment_gateway_mode ?? 'app',
                'stripe_public_key' => $settings->stripe_public_key,
                'has_stripe_secret' => filled($settings->stripe_secret_key),
                'bank_name' => $settings->bank_name,
                'bank_account_name' => $settings->bank_account_name,
                'bank_account_number' => $settings->bank_account_number,
                'paypal_email' => $settings->paypal_email,
                'tax_enabled' => (bool) $settings->tax_enabled,
                'default_tax_rate' => $settings->default_tax_rate,
                'primary_color' => $settings->primary_color ?? '#4f46e5',
                'invoice_template' => $settings->invoice_template ?? 'modern',
            ],
            'invoiceTemplates' => CompanySetting::invoiceTemplates(),
            'colorPresets' => CompanySetting::colorPresets(),
            'plans' => SubscriptionPlan::active()->ordered()->get()
                ->map(fn ($p) => $p->only('id', 'name', 'slug', 'price', 'billing_cycle', 'trial_days', 'description', 'features', 'max_invoices', 'max_clients', 'max_users'))
                ->values(),
            'currentPlanId' => $company->subscription_plan_id,
            'teamMembers' => $company->users()->where('id', '!=', $user->id)->get(['id', 'name', 'email', 'role']),
            'step' => max(1, min(self::TOTAL_STEPS, (int) $company->onboarding_step + 1)),
            'totalSteps' => self::TOTAL_STEPS,
        ]);
    }

    /**
     * Save one wizard step.
     */
    public function updateStep(Request $request, int $step)
    {
        abort_unless($step >= 1 && $step <= self::TOTAL_STEPS, 404);

        $user = auth()->user();
        $company = $user->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);
        $message = 'Saved.';

        switch ($step) {
            case 1: // Company logo
                $request->validate([
                    'logo' => 'nullable|image|max:2048',
                ]);

                if ($request->hasFile('logo')) {
                    if ($company->logo) {
                        Storage::disk('public')->delete($company->logo);
                    }
                    $company->logo = $request->file('logo')->store('logos', 'public');
                    $company->save();
                }
                break;

            case 2: // Company address
                $company->update($request->validate([
                    'address' => 'nullable|string|max:500',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20',
                    'country' => 'nullable|string|max:100',
                    'phone' => 'nullable|string|max:50',
                ]));
                break;

            case 3: // Payment methods
                $request->validate([
                    'accept_bank_transfer' => 'boolean',
                    'accept_cash' => 'boolean',
                    'accept_stripe' => 'boolean',
                    'payment_gateway_mode' => 'nullable|string|in:app,own',
                    'stripe_public_key' => 'nullable|string|max:255|starts_with:pk_',
                    'stripe_secret_key' => 'nullable|string|max:255|starts_with:sk_,rk_',
                    'bank_name' => 'nullable|string|max:255',
                    'bank_account_name' => 'nullable|string|max:255',
                    'bank_account_number' => 'nullable|string|max:100',
                    'paypal_email' => 'nullable|email|max:255',
                ]);

                $updateData = [
                    'accept_bank_transfer' => $request->boolean('accept_bank_transfer'),
                    'accept_cash' => $request->boolean('accept_cash'),
                    'accept_stripe' => $request->boolean('accept_stripe'),
                    'stripe_enabled' => $request->boolean('accept_stripe'),
                    'payment_gateway_mode' => $request->input('payment_gateway_mode', 'app'),
                    'bank_name' => $request->input('bank_name'),
                    'bank_account_name' => $request->input('bank_account_name'),
                    'bank_account_number' => $request->input('bank_account_number'),
                    'paypal_email' => $request->input('paypal_email'),
                ];

                // Own Stripe account: store keys in the real columns (previously written to
                // non-existent stripe_key/stripe_secret attributes and silently dropped).
                // A blank secret keeps the existing one, since it is never sent to the browser.
                if ($request->input('payment_gateway_mode') === 'own') {
                    if ($request->filled('stripe_public_key')) {
                        $updateData['stripe_public_key'] = $request->input('stripe_public_key');
                    }
                    if ($request->filled('stripe_secret_key')) {
                        $updateData['stripe_secret_key'] = $request->input('stripe_secret_key');
                    }
                }

                $settings->update($updateData);
                break;

            case 4: // Tax
                $request->validate([
                    'tax_enabled' => 'boolean',
                    'default_tax_rate' => 'nullable|numeric|min:0|max:100',
                    'tax_number' => 'nullable|string|max:100',
                ]);

                $settings->update([
                    'tax_enabled' => $request->boolean('tax_enabled'),
                    'default_tax_rate' => $request->input('default_tax_rate'),
                ]);
                $company->update(['tax_number' => $request->input('tax_number')]);
                break;

            case 5: // Brand colour & invoice template
                $request->validate([
                    'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'invoice_template' => 'nullable|string|in:' . implode(',', array_keys(CompanySetting::invoiceTemplates())),
                ]);

                $settings->update([
                    'primary_color' => $request->input('primary_color', '#4f46e5'),
                    'invoice_template' => $request->input('invoice_template', 'modern'),
                ]);
                break;

            case 6: // Invite a team member
                $request->validate([
                    'team_name' => 'nullable|required_with:team_email|string|max:255',
                    'team_email' => 'nullable|email:rfc,filter|max:255|unique:users,email',
                    'team_role' => 'nullable|in:admin,member',
                ]);

                if ($request->filled('team_email')) {
                    if ($company->hasReachedLimit('users')) {
                        return back()->with('error', 'Your plan’s team member limit has been reached.');
                    }

                    // Random unusable password; the invitee sets their own via the emailed link.
                    // (Previously every invitee got the hard-coded password "password123".)
                    $member = User::create([
                        'name' => $request->input('team_name'),
                        'email' => Str::lower($request->input('team_email')),
                        'password' => Str::password(40),
                        'company_id' => $company->id,
                        'role' => $request->input('team_role', 'member'),
                    ]);
                    $member->markEmailAsVerified();

                    Mail::to($member->email)->queue(new TeamInviteMail($member, $user->name, $company->name, PasswordResetLink::for($member)));
                    $message = "Invitation sent to {$member->email}.";
                }
                break;

            case 7: // Plan (handled by SubscriptionController)
                break;
        }

        $company->onboarding_step = max((int) $company->onboarding_step, $step);
        $company->save();

        return back()->with('success', $message);
    }

    /**
     * Skip onboarding and go to dashboard
     */
    public function skip()
    {
        $company = auth()->user()->company;
        $company->onboarding_completed = true;
        $company->save();

        return redirect()->route('dashboard')->with('success', 'Welcome! You can complete your setup anytime from Settings.');
    }

    /**
     * Mark onboarding as complete
     */
    public function complete()
    {
        $company = auth()->user()->company;
        $company->onboarding_completed = true;
        $company->onboarding_step = self::TOTAL_STEPS;
        $company->save();

        return redirect()->route('dashboard')->with('success', 'Setup complete! Welcome to your dashboard.');
    }
}
