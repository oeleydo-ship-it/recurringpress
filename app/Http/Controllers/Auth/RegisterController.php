<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $planSlug = $request->query('plan', 'free');
        $plan = \App\Models\SubscriptionPlan::where('slug', $planSlug)->first();

        return Inertia::render('Auth/Register', [
            'plan' => $plan?->only('name', 'slug', 'price'),
            'planSlug' => $plan?->slug ?? 'free',
        ]);
    }

    public function register(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'name' => trim(preg_replace('/\s+/u', ' ', (string) $request->input('name'))),
            'company_name' => trim(preg_replace('/\s+/u', ' ', (string) $request->input('company_name'))),
        ]);

        // Spam bots stuff URLs / HTML into name fields so they get echoed into welcome emails.
        $noLinksOrMarkup = 'not_regex:/(https?:\/\/|www\.|<|>|\[url)/i';

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', $noLinksOrMarkup],
            'email' => [
                'required', 'string', 'email:rfc,filter', 'max:255', 'unique:users,email',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $domain = Str::after((string) $value, '@');
                    if (in_array($domain, config('security.disposable_email_domains', []), true)) {
                        $fail('Please use a permanent email address.');
                    }
                },
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'min:2', 'max:150', $noLinksOrMarkup],
            'plan' => ['nullable', 'string', 'max:100', 'exists:subscription_plans,slug'],
        ], [
            'name.not_regex' => 'Your name may not contain links or HTML.',
            'company_name.not_regex' => 'Company name may not contain links or HTML.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $planSlug = $request->input('plan', 'free');
            $plan = \App\Models\SubscriptionPlan::where('slug', $planSlug)->first();

            // Fallback to free plan or first available plan if requested plan doesn't exist
            if (!$plan) {
                $plan = \App\Models\SubscriptionPlan::where('slug', 'free')->first()
                    ?? \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('order')->first();
            }

            // If still no plan, create a basic free plan on the fly
            if (!$plan) {
                $plan = \App\Models\SubscriptionPlan::create([
                    'name' => 'Free',
                    'slug' => 'free',
                    'description' => 'Basic free plan',
                    'price' => 0,
                    'billing_cycle' => 'monthly',
                    'is_active' => true,
                    'order' => 1,
                ]);
            }

            $trialDays = 14;
            $status = 'trial';
            $endsAt = now()->addDays($trialDays);

            // If it's a completely free plan (price 0), we might just make it active immediately
            if ($plan->price <= 0) {
                $status = 'active';
                $endsAt = null; // Unlimited for free plan? Or maybe null means forever.
            }

            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'currency' => 'USD',
                'invoice_prefix' => 'INV-',
                'invoice_start_number' => 1,
                'payment_terms' => 30,
                'subscription_plan_id' => $plan->id,
                'subscription_status' => $status,
                'subscription_started_at' => now(),
                'subscription_ends_at' => $endsAt,
            ]);

            CompanySetting::create([
                'company_id' => $company->id,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
                'role' => User::ROLE_OWNER,
            ]);

            return $user;
        });

        // Check if email verification is enabled in system settings
        $emailVerificationEnabled = \App\Models\SystemSetting::get('email_verification_enabled', '0') === '1';

        if (!$emailVerificationEnabled) {
            // Auto-verify the user if email verification is disabled
            $user->markEmailAsVerified();
        }

        Auth::login($user);

        // Only send verification email if enabled
        if ($emailVerificationEnabled) {
            try {
                event(new \Illuminate\Auth\Events\Registered($user));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send verification email: ' . $e->getMessage());
                // Continue anyway - user is registered, they can request resend later
            }
        }

        return redirect()->route('onboarding.welcome');
    }
}
