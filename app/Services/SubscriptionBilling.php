<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single code path that turns a successful plan PaymentIntent into a subscription term.
 * Plans are sold as prepaid terms: Stripe charges once and nothing renews automatically, so
 * monthly and yearly tenants pay again from the billing page and a lifetime payment never ends.
 */
class SubscriptionBilling
{
    /** Stripe keeps retrying webhooks for days, but an old intent must not be reusable after that. */
    private const INTENT_MAX_AGE_DAYS = 7;

    public static function intentMetadata(Company $company, SubscriptionPlan $plan, string $period): array
    {
        return [
            'type' => 'subscription_upgrade',
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'billing_period' => $period,
        ];
    }

    /** Intents created before billing periods existed carry no period: they bought the plan's default. */
    public static function periodFromIntent(object $intent, SubscriptionPlan $plan): ?string
    {
        $period = $intent->metadata->billing_period ?? $plan->billing_cycle;

        return in_array($period, SubscriptionPlan::PERIODS, true) ? $period : null;
    }

    /**
     * The amount is fixed by our server when the intent is created, so it is compared with the
     * intent itself: a price edited while the customer is paying must not strand their payment.
     */
    public static function intentIsValid(object $intent, Company $company, SubscriptionPlan $plan): bool
    {
        return $intent->status === 'succeeded'
            && ($intent->metadata->type ?? null) === 'subscription_upgrade'
            && (int) ($intent->metadata->company_id ?? 0) === $company->id
            && (int) ($intent->metadata->plan_id ?? 0) === $plan->id
            && self::periodFromIntent($intent, $plan) !== null
            && (int) $intent->amount > 0
            && (int) $intent->amount_received >= (int) $intent->amount
            && strtolower((string) $intent->currency) === 'usd'
            && (int) $intent->created >= now()->subDays(self::INTENT_MAX_AGE_DAYS)->timestamp;
    }

    /** Idempotent: returns true only for the call that actually applied this payment. */
    public static function applyPayment(Company $company, SubscriptionPlan $plan, object $intent): bool
    {
        $period = self::periodFromIntent($intent, $plan);

        try {
            return DB::transaction(function () use ($company, $plan, $intent, $period) {
                $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();

                if (SubscriptionPayment::where('stripe_payment_intent_id', $intent->id)->exists()) {
                    return false;
                }

                SubscriptionPayment::create([
                    'company_id' => $company->id,
                    'subscription_plan_id' => $plan->id,
                    'billing_period' => $period,
                    'amount' => round((int) $intent->amount_received / 100, 2),
                    'currency' => strtolower((string) $intent->currency),
                    'stripe_payment_intent_id' => $intent->id,
                ]);

                if (self::wouldDowngradeLifetime($company, $plan, $period, $intent)) {
                    Log::warning('Subscription payment did not replace a lifetime subscription', ['company_id' => $company->id, 'intent' => $intent->id]);

                    return false;
                }

                $extendsTerm = $period !== SubscriptionPlan::LIFETIME
                    && $company->subscription_plan_id === $plan->id
                    && $company->subscription_status === 'active'
                    && $company->subscription_ends_at?->isFuture();

                $company->update([
                    'subscription_plan_id' => $plan->id,
                    'subscription_status' => 'active',
                    'billing_period' => $period,
                    'subscription_started_at' => $extendsTerm ? $company->subscription_started_at : now(),
                    // Paying early adds to the time already paid for instead of discarding it.
                    'subscription_ends_at' => SubscriptionPlan::termEnd($period, $extendsTerm ? $company->subscription_ends_at : now()),
                ]);

                TenantNotifier::notify(
                    $company,
                    'plan_changed',
                    $period === SubscriptionPlan::LIFETIME ? "Lifetime access to {$plan->name}" : "Subscribed to {$plan->name}",
                    $period === SubscriptionPlan::LIFETIME
                        ? 'Payment received — no renewal needed.'
                        : 'Payment received — paid through ' . $company->subscription_ends_at->format('M j, Y') . '.',
                    TenantNotifier::path('settings.subscription'),
                    TenantNotifier::AUDIENCE_ADMINS,
                );

                return true;
            });
        } catch (QueryException $e) {
            // The redirect and the webhook raced and the other one recorded the intent first.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * A recurring payment never replaces lifetime access to the same plan, and one that was started
     * before the lifetime purchase (a late webhook or redirect) never replaces it on any plan.
     */
    private static function wouldDowngradeLifetime(Company $company, SubscriptionPlan $plan, ?string $period, object $intent): bool
    {
        if (! $company->isLifetime() || $period === SubscriptionPlan::LIFETIME) {
            return false;
        }

        return $company->subscription_plan_id === $plan->id
            || (int) $intent->created < (int) $company->subscription_started_at?->timestamp;
    }
}
