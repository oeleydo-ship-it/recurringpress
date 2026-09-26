<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CompanySetting;
use App\Models\SubscriptionPlan;

class OnboardingController extends Controller
{
    /**
     * Total number of onboarding steps
     */
    const TOTAL_STEPS = 7;

    /**
     * Display welcome page after email verification
     */
    public function welcome()
    {
        $user = auth()->user();
        $company = $user->company;

        // If already completed onboarding, redirect to dashboard
        if ($company->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.welcome', compact('user', 'company'));
    }

    /**
     * Display setup wizard
     */
    public function setup()
    {
        $user = auth()->user();
        $company = $user->company;

        // If already completed onboarding, redirect to dashboard
        if ($company->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);
        $currencies = CompanySetting::currencies();
        $invoiceTemplates = CompanySetting::invoiceTemplates();
        $colorPresets = CompanySetting::colorPresets();
        $fontFamilies = CompanySetting::fontFamilies();
        $plans = SubscriptionPlan::active()->ordered()->get();
        $currentPlan = $company->subscriptionPlan;
        $teamMembers = $company->users()->where('id', '!=', $user->id)->get();

        return view('onboarding.setup', compact(
            'user',
            'company',
            'settings',
            'currencies',
            'invoiceTemplates',
            'colorPresets',
            'fontFamilies',
            'plans',
            'currentPlan',
            'teamMembers'
        ));
    }

    /**
     * Update a specific step
     */
    public function updateStep(Request $request, int $step)
    {
        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        switch ($step) {
            case 1: // Company Logo
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

            case 2: // Company Address
                $request->validate([
                    'address' => 'nullable|string|max:500',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20',
                    'country' => 'nullable|string|max:100',
                    'phone' => 'nullable|string|max:50',
                ]);

                $company->update($request->only(['address', 'city', 'state', 'postal_code', 'country', 'phone']));
                break;

            case 3: // Payment Gateway
                $request->validate([
                    'accept_bank_transfer' => 'boolean',
                    'accept_cash' => 'boolean',
                    'accept_stripe' => 'boolean',
                    'payment_gateway_mode' => 'nullable|string|in:app,own',
                    'stripe_key' => 'nullable|string|max:255',
                    'stripe_secret' => 'nullable|string|max:255',
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

                // Only update Stripe keys if using own account
                if ($request->input('payment_gateway_mode') === 'own') {
                    if ($request->filled('stripe_key')) {
                        $updateData['stripe_key'] = $request->input('stripe_key');
                    }
                    if ($request->filled('stripe_secret')) {
                        $updateData['stripe_secret'] = $request->input('stripe_secret');
                    }
                }

                $settings->update($updateData);
                break;

            case 4: // Tax Settings
                $request->validate([
                    'tax_enabled' => 'boolean',
                    'default_tax_rate' => 'nullable|numeric|min:0|max:100',
                    'tax_number' => 'nullable|string|max:100',
                ]);

                $settings->update([
                    'tax_enabled' => $request->boolean('tax_enabled'),
                    'default_tax_rate' => $request->input('default_tax_rate'),
                ]);

                $company->update([
                    'tax_number' => $request->input('tax_number'),
                ]);
                break;

            case 5: // Theme & Invoice Template
                $request->validate([
                    'primary_color' => 'nullable|string|max:7',
                    'invoice_template' => 'nullable|string|in:classic,modern,minimal,corporate,elegant,creative,professional,simple,bold,striped,tech,luxe',
                ]);

                $settings->update([
                    'primary_color' => $request->input('primary_color', '#6366f1'),
                    'invoice_template' => $request->input('invoice_template', 'modern'),
                ]);
                break;

            case 6: // Add Team Members
                $request->validate([
                    'team_name' => 'nullable|string|max:255',
                    'team_email' => 'nullable|email|max:255|unique:users,email',
                    'team_role' => 'nullable|in:admin,member',
                ]);

                if ($request->filled('team_email')) {
                    User::create([
                        'name' => $request->input('team_name'),
                        'email' => $request->input('team_email'),
                        'password' => Hash::make('password123'), // Temporary password
                        'company_id' => $company->id,
                        'role' => $request->input('team_role', 'member'),
                    ]);
                }
                break;

            case 7: // Upgrade Plan (handled separately via SubscriptionController)
                // Just advance the step
                break;
        }

        // Update current step
        $company->onboarding_step = $step;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => 'Step saved successfully',
            'current_step' => $step,
        ]);
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
