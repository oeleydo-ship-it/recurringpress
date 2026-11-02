<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Ai\AiClient;
use App\Services\StripeGateway;
use App\Services\SubscriptionBilling;
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
            'billingPeriod' => $currentPlan && ! $currentPlan->isFree() ? ($company->billing_period ?? $currentPlan->billing_cycle) : null,
            'isLifetime' => $company->isLifetime(),
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

        if ($plan->isFree()) {
            $company->update([
                'subscription_plan_id' => $plan->id,
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
                'billing_period' => null,
            ]);

            TenantNotifier::notify($company, 'plan_changed', "Plan changed to {$plan->name}", 'Switched' . TenantNotifier::byActor() . '.', TenantNotifier::path('settings.subscription'), TenantNotifier::AUDIENCE_ADMINS);

            return redirect()->route('settings.subscription')
                ->with('success', 'Switched to the ' . $plan->name . ' plan.');
        }

        return redirect()->route('settings.subscription.payment', ['plan' => $plan, 'period' => $request->query('period')]);
    }

    public function showPaymentForm(Request $request, SubscriptionPlan $plan)
    {
        $period = $this->requestedPeriod($request, $plan);
        abort_unless($plan->is_active && $period, 404);

        $company = auth()->user()->company;

        if ($company->isLifetime() && $company->subscription_plan_id === $plan->id) {
            return redirect()->route('settings.subscription')->with('error', 'You already have lifetime access to this plan.');
        }

        if (! $this->platformReady()) {
            return redirect()->route('settings.subscription')
                ->with('error', 'Online payment is not configured. Please contact support.');
        }

        return Inertia::render('Settings/SubscriptionPayment', [
            'plan' => $this->planPayload($plan),
            'period' => $period,
            'amount' => $plan->priceFor($period),
            // A different plan bought on a recurring term ends the lifetime access the company has now.
            'replacesLifetime' => $company->isLifetime() && $period !== SubscriptionPlan::LIFETIME ? $company->subscriptionPlan?->name : null,
            'stripeKey' => StripeGateway::publishableKey(null),
            'intentUrl' => route('settings.subscription.intent', ['plan' => $plan, 'period' => $period]),
            'returnUrl' => route('settings.subscription.success', ['plan' => $plan, 'period' => $period]),
        ]);
    }

    public function createPaymentIntent(Request $request, SubscriptionPlan $plan)
    {
        $period = $this->requestedPeriod($request, $plan);

        if (! $plan->is_active || ! $period) {
            return response()->json(['error' => 'This plan is not available.'], 404);
        }

        $company = auth()->user()->company;

        if ($company->isLifetime() && $company->subscription_plan_id === $plan->id) {
            return response()->json(['error' => 'You already have lifetime access to this plan.'], 422);
        }

        if (! $this->platformReady()) {
            return response()->json(['error' => 'Payment gateway not configured'], 400);
        }

        Stripe::setApiKey(StripeGateway::secretKey(null));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($plan->priceFor($period) * 100),
                'currency' => 'usd',
                'description' => $period === SubscriptionPlan::LIFETIME ? "Lifetime access: {$plan->name}" : "Subscription ({$period}): {$plan->name}",
                'metadata' => SubscriptionBilling::intentMetadata($company, $plan, $period),
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
     * must now belong to this company and plan and be paid in full; its metadata sets the period.
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

            return redirect()->route('settings.subscription.payment', ['plan' => $plan, 'period' => $request->query('period')])->with('error', 'Payment verification failed.');
        }

        $company = auth()->user()->company;
        $valid = SubscriptionBilling::intentIsValid($intent, $company, $plan);

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

        // The webhook may have got here first; applying twice is a no-op.
        SubscriptionBilling::applyPayment($company, $plan, $intent);

        return redirect()->route('settings.subscription')
            ->with('success', 'Payment received — you are now on the ' . $plan->name . ' plan.');
    }

    /* ------------------------------------------------------------------ */

    private function platformReady(): bool
    {
        return filled(StripeGateway::secretKey(null)) && filled(StripeGateway::publishableKey(null));
    }

    /** The period to charge: the requested one if the plan offers it, or the plan's default when none was asked for. */
    private function requestedPeriod(Request $request, SubscriptionPlan $plan): ?string
    {
        $period = $request->query('period') ?: $plan->defaultPeriod();

        return $plan->offersPeriod($period) ? $period : null;
    }

    private function planPayload(SubscriptionPlan $p): array
    {
        return [
            ...$p->only('id', 'name', 'slug', 'description', 'trial_days', 'max_invoices', 'max_clients', 'max_users', 'max_products', 'custom_branding', 'white_label', 'custom_domain', 'subdomain', 'client_portal', 'stripe_enabled', 'is_popular'),
            ...$p->pricingPayload(),
            'features' => $p->features ?? [],
            // Monthly AI requests the plan includes; null when AI is off for the plan or the platform.
            'ai_requests' => $p->ai_enabled && AiClient::enabled() ? ($p->max_ai_requests ?? AiClient::monthlyQuota()) : null,
        ];
    }
}
