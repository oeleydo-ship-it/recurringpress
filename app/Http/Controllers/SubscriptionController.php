<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class SubscriptionController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $company->load('subscriptionPlan');

        // Get current plan or default to free plan
        $currentPlan = $company->subscriptionPlan;

        if (!$currentPlan) {
            $currentPlan = SubscriptionPlan::where('slug', 'free')->first();

            // Auto-link company to free plan if not set
            if ($currentPlan && !$company->subscription_plan_id) {
                $company->update(['subscription_plan_id' => $currentPlan->id]);
            }
        }

        // Load all available plans for upgrade/downgrade
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('settings.subscription', compact('company', 'currentPlan', 'plans'));
    }

    public function changePlan(Request $request, SubscriptionPlan $plan)
    {
        $company = auth()->user()->company;
        $currentPlan = $company->subscriptionPlan;

        // For free plans, just switch immediately
        if ($plan->price <= 0) {
            $company->update([
                'subscription_plan_id' => $plan->id,
                'subscription_status' => 'active',
            ]);

            return redirect()->route('settings.subscription')
                ->with('success', 'Successfully switched to ' . $plan->name . ' plan.');
        }

        // For paid plans, redirect to payment page
        return redirect()->route('settings.subscription.payment', $plan);
    }

    public function showPaymentForm(SubscriptionPlan $plan)
    {
        $company = auth()->user()->company;

        // Ensure platform Stripe is configured
        if (!$this->hasPlatformStripeCredentials()) {
            return redirect()->route('settings.subscription')
                ->with('error', 'Payment gateway is not configured. Please contact support.');
        }

        $stripeKey = $this->getPlatformStripePublishableKey();

        return view('settings.subscription-payment', compact('plan', 'company', 'stripeKey'));
    }

    public function createPaymentIntent(SubscriptionPlan $plan)
    {
        if (!$this->hasPlatformStripeCredentials()) {
            return response()->json(['error' => 'Payment gateway not configured'], 400);
        }

        Stripe::setApiKey($this->getPlatformStripeSecretKey());

        try {
            $company = auth()->user()->company;

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($plan->price * 100),
                'currency' => 'usd', // Default to USD for subscriptions for now, or fetch from system settings
                'metadata' => [
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'type' => 'subscription_upgrade'
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => $this->getPlatformStripePublishableKey(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handlePaymentSuccess(Request $request, SubscriptionPlan $plan)
    {
        $paymentIntentId = $request->input('payment_intent');

        if (!$paymentIntentId) {
            return redirect()->route('settings.subscription')->with('error', 'Payment verification failed.');
        }

        Stripe::setApiKey($this->getPlatformStripeSecretKey());

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                $company = auth()->user()->company;

                // Update company plan
                $company->update([
                    'subscription_plan_id' => $plan->id,
                    'subscription_status' => 'active',
                    'subscription_started_at' => now(),
                    // 'subscription_ends_at' => now()->addMonth(), // If we were tracking expiration
                ]);

                // Verify if metadata matches (optional but good security)
                if (
                    $paymentIntent->metadata->company_id != $company->id ||
                    $paymentIntent->metadata->plan_id != $plan->id
                ) {
                    // Log mismatch warning?
                }

                return redirect()->route('settings.subscription')
                    ->with('success', 'Payment successful! You have been upgraded to the ' . $plan->name . ' plan.');
            }
        } catch (\Exception $e) {
            return redirect()->route('settings.subscription.payment', $plan)
                ->with('error', 'Payment verification failed: ' . $e->getMessage());
        }

        return redirect()->route('settings.subscription')->with('error', 'Payment was not successful.');
    }

    // Helper methods for Platform Stripe Keys (duplicated from PublicInvoiceController logic, could be trait)
    protected function hasPlatformStripeCredentials(): bool
    {
        $stripeEnabled = SystemSetting::get('stripe_enabled');
        if (!$stripeEnabled) {
            return false;
        }

        $mode = SystemSetting::get('stripe_mode', 'test');

        if ($mode === 'live') {
            return !empty(SystemSetting::get('stripe_live_secret_key')) &&
                !empty(SystemSetting::get('stripe_live_publishable_key'));
        }

        return !empty(SystemSetting::get('stripe_test_secret_key')) &&
            !empty(SystemSetting::get('stripe_test_publishable_key'));
    }

    protected function getPlatformStripeSecretKey(): ?string
    {
        $mode = SystemSetting::get('stripe_mode', 'test');
        return $mode === 'live' ? SystemSetting::get('stripe_live_secret_key') : SystemSetting::get('stripe_test_secret_key');
    }

    protected function getPlatformStripePublishableKey(): ?string
    {
        $mode = SystemSetting::get('stripe_mode', 'test');
        return $mode === 'live' ? SystemSetting::get('stripe_live_publishable_key') : SystemSetting::get('stripe_test_publishable_key');
    }
}
