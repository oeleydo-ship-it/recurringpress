<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\StripeGateway;
use App\Services\TenantNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company->load('subscriptionPlan');
        $currentPlan = $company->subscriptionPlan;

        if (! $currentPlan) {
            $currentPlan = SubscriptionPlan::where('slug', 'free')->first();

            if ($currentPlan && ! $company->subscription_plan_id) {
                $company->update(['subscription_plan_id' => $currentPlan->id]);
            }
        }

        return Inertia::render('Settings/Subscription', [
            'currentPlanId' => $currentPlan?->id,
            'status' => $company->subscription_status,
            'hasPlanAccess' => $company->hasPlanAccess(),
            'endsAt' => $company->subscription_ends_at?->toDateString(),
            'plans' => SubscriptionPlan::active()->ordered()->get()->map(fn ($p) => $this->planPayload($p)),
            'usage' => [
                'invoices' => $company->invoices()->count(),
                'clients' => $company->clients()->count(),
                'users' => $company->users()->count(),
                'products' => $company->products()->count(),
            ],
            'canPayOnline' => $this->platformReady(),
        ]);
    }

    public function changePlan(Request $request, SubscriptionPlan $plan)
    {
        // Only publicly offered plans (previously any plan id, including inactive ones).
        abort_unless($plan->is_active, 404);

        $company = auth()->user()->company;

        if ((float) $plan->price <= 0) {
            $company->update([
                'subscription_plan_id' => $plan->id,
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
            ]);

            TenantNotifier::notify($company, 'plan_changed', "Plan changed to {$plan->name}", 'Switched' . TenantNotifier::byActor() . '.', TenantNotifier::path('settings.subscription'), TenantNotifier::AUDIENCE_ADMINS);

            return redirect()->route('settings.subscription')
                ->with('success', 'Switched to the ' . $plan->name . ' plan.');
        }

        return redirect()->route('settings.subscription.payment', $plan);
    }

    public function showPaymentForm(SubscriptionPlan $plan)
    {
        abort_unless($plan->is_active && (float) $plan->price > 0, 404);

        if (! $this->platformReady()) {
            return redirect()->route('settings.subscription')
                ->with('error', 'Online payment is not configured. Please contact support.');
        }

        return Inertia::render('Settings/SubscriptionPayment', [
            'plan' => $this->planPayload($plan),
            'stripeKey' => StripeGateway::publishableKey(null),
            'intentUrl' => route('settings.subscription.intent', $plan),
            'returnUrl' => route('settings.subscription.success', $plan),
        ]);
    }

    public function createPaymentIntent(SubscriptionPlan $plan)
    {
        if (! $plan->is_active || (float) $plan->price <= 0) {
            return response()->json(['error' => 'This plan is not available.'], 404);
        }

        if (! $this->platformReady()) {
            return response()->json(['error' => 'Payment gateway not configured'], 400);
        }

        Stripe::setApiKey(StripeGateway::secretKey(null));

        try {
            $company = auth()->user()->company;

            $paymentIntent = PaymentIntent::create([
                'amount' => $this->amountInCents($plan),
                'currency' => 'usd',
                'description' => "Subscription: {$plan->name}",
                'metadata' => [
                    'type' => 'subscription_upgrade',
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => StripeGateway::publishableKey(null),
            ]);
        } catch (\Throwable $e) {
            Log::error('Subscription PaymentIntent failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not start the payment. Please try again.'], 500);
        }
    }

    /**
     * Stripe redirects here after confirmation. Previously ANY succeeded PaymentIntent upgraded
     * the company to whatever plan was in the URL (the metadata check ran after the upgrade
     * and did nothing), so paying for the cheapest plan once unlocked every plan. The intent
     * must now belong to this company and plan, and the amount paid must cover the price.
     */
    public function handlePaymentSuccess(Request $request, SubscriptionPlan $plan)
    {
        $paymentIntentId = (string) $request->input('payment_intent');

        if (! str_starts_with($paymentIntentId, 'pi_') || ! $this->platformReady()) {
            return redirect()->route('settings.subscription')->with('error', 'Payment verification failed.');
        }

        Stripe::setApiKey(StripeGateway::secretKey(null));

        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);
        } catch (\Throwable $e) {
            Log::warning('Subscription payment verification failed', ['error' => $e->getMessage()]);

            return redirect()->route('settings.subscription.payment', $plan)->with('error', 'Payment verification failed.');
        }

        $company = auth()->user()->company;
        $valid = $intent->status === 'succeeded'
            && ($intent->metadata->type ?? null) === 'subscription_upgrade'
            && (int) ($intent->metadata->company_id ?? 0) === $company->id
            && (int) ($intent->metadata->plan_id ?? 0) === $plan->id
            && (int) $intent->amount_received >= $this->amountInCents($plan)
            && strtolower((string) $intent->currency) === 'usd';

        if (! $valid) {
            Log::warning('Rejected subscription payment', [
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'intent' => $intent->id,
                'status' => $intent->status,
                'metadata' => (array) $intent->metadata?->toArray(),
            ]);

            return redirect()->route('settings.subscription')->with('error', 'This payment could not be verified for the selected plan.');
        }

        $company->update([
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_ends_at' => $plan->billing_cycle === 'yearly' ? now()->addYearNoOverflow() : now()->addMonthNoOverflow(),
        ]);

        TenantNotifier::notify(
            $company,
            'plan_changed',
            "Subscribed to {$plan->name}",
            'Payment received — renews on ' . $company->subscription_ends_at->format('M j, Y') . '.',
            TenantNotifier::path('settings.subscription'),
            TenantNotifier::AUDIENCE_ADMINS,
        );

        return redirect()->route('settings.subscription')
            ->with('success', 'Payment received — you are now on the ' . $plan->name . ' plan.');
    }

    /* ------------------------------------------------------------------ */

    private function platformReady(): bool
    {
        return filled(StripeGateway::secretKey(null)) && filled(StripeGateway::publishableKey(null));
    }

    private function amountInCents(SubscriptionPlan $plan): int
    {
        return (int) round((float) $plan->price * 100);
    }

    private function planPayload(SubscriptionPlan $p): array
    {
        return [
            ...$p->only('id', 'name', 'slug', 'description', 'billing_cycle', 'trial_days', 'max_invoices', 'max_clients', 'max_users', 'max_products', 'custom_branding', 'white_label', 'custom_domain', 'subdomain', 'stripe_enabled', 'is_popular'),
            'price' => (float) $p->price,
            'features' => $p->features ?? [],
        ];
    }
}
