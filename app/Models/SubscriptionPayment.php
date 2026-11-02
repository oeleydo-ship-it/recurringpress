<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'billing_period',
        'amount',
        'currency',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
