<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Services\StripeGateway;

/**
 * Client-facing payloads for public invoice/quotation pages. Only fields meant for the
 * client are included (never gateway secrets or internal settings).
 */
class PublicDocument
{
    public static function company(Company $company): array
    {
        $settings = $company->settings;

        return [
            'name' => $company->name,
            'logo_url' => $company->logo_url,
            'email' => $company->email,
            'phone' => $company->phone,
            'website' => $company->website,
            'address' => collect([$company->address, $company->city, $company->state, $company->postal_code, $company->country])->filter()->implode(', '),
            'tax_number' => $company->tax_number,
            'brand' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($settings->primary_color ?? '')) ? $settings->primary_color : '#4f46e5',
        ];
    }

    public static function paymentOptions(?CompanySetting $settings): array
    {
        return [
            'stripe' => StripeGateway::available($settings),
            'stripe_key' => StripeGateway::available($settings) ? StripeGateway::publishableKey($settings) : null,
            'bank' => $settings?->accept_bank_transfer ? array_filter([
                'bank_name' => $settings->bank_name,
                'account_name' => $settings->bank_account_name,
                'account_number' => $settings->bank_account_number,
                'routing_number' => $settings->bank_routing_number,
            ]) : null,
            'cash' => (bool) $settings?->accept_cash,
            'paypal_email' => $settings?->paypal_email,
            'instructions' => $settings?->show_payment_instructions ? $settings->payment_instructions : null,
        ];
    }
}
