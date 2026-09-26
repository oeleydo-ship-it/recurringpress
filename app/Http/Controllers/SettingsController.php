<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private const HEX = 'regex:/^#[0-9a-fA-F]{6}$/';

    public function index()
    {
        $company = auth()->user()->company;
        $settings = $this->settings();

        return Inertia::render('Settings/Index', [
            'company' => [
                ...$company->only(['name', 'email', 'phone', 'website', 'tax_number', 'address', 'city', 'state', 'postal_code', 'country', 'currency',
                    'invoice_prefix', 'invoice_start_number', 'payment_terms', 'invoice_footer', 'quotation_prefix', 'quotation_validity_days']),
                'logo_url' => $company->logo_url,
                'signature_url' => $company->signature_url,
            ],
            'settings' => [
                ...$settings->only([
                    'date_format', 'timezone', 'tax_enabled', 'default_tax_rate', 'email_notifications', 'invoice_due_reminder', 'overdue_reminder',
                    'show_logo_on_invoice', 'show_payment_instructions', 'payment_instructions', 'bank_name', 'bank_account_name', 'bank_account_number',
                    'bank_routing_number', 'paypal_email', 'accept_bank_transfer', 'accept_cash', 'accept_stripe', 'payment_gateway_mode', 'stripe_enabled',
                    'stripe_public_key', 'primary_color', 'secondary_color', 'sidebar_color', 'sidebar_text_color', 'invoice_template', 'font_family',
                    'font_size', 'heading_color', 'text_color', 'default_notes', 'default_terms', 'show_authorized_signature', 'show_invoice_footer',
                    'show_quotation_website', 'show_quotation_message', 'quotation_message',
                ]),
                // Secrets are never sent to the browser; only whether one is saved.
                'has_stripe_secret' => filled($settings->stripe_secret_key),
                'has_webhook_secret' => filled($settings->stripe_webhook_secret),
            ],
            'options' => [
                'currencies' => CompanySetting::currencies(),
                'dateFormats' => CompanySetting::dateFormats(),
                'invoiceTemplates' => CompanySetting::invoiceTemplates(),
                'colorPresets' => CompanySetting::colorPresets(),
                'fontFamilies' => CompanySetting::fontFamilies(),
                'fontSizes' => CompanySetting::fontSizes(),
                'timezones' => \DateTimeZone::listIdentifiers(),
            ],
            'webhookUrl' => route('stripe.webhook'),
        ]);
    }

    public function updateCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email:rfc,filter|max:255',
            'phone' => 'nullable|string|max:50',
            // http(s) only — the plain url rule also accepts schemes like javascript:.
            'website' => 'nullable|url:http,https|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'currency' => ['required', Rule::in(array_keys(CompanySetting::currencies()))],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'remove_logo' => 'boolean',
        ]);

        $company = auth()->user()->company;

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo') && $company->logo) {
            Storage::disk('public')->delete($company->logo);
            $validated['logo'] = null;
        } else {
            unset($validated['logo']);
        }
        unset($validated['remove_logo']);

        $company->update($validated);

        return back()->with('success', 'Company details saved.');
    }

    public function updateInvoice(Request $request)
    {
        $validated = $request->validate([
            'invoice_prefix' => 'required|string|max:20|regex:/^[A-Za-z0-9_\-\/#.]*$/',
            'invoice_start_number' => 'required|integer|min:1|max:99999999',
            'payment_terms' => 'required|integer|min:0|max:365',
            'invoice_footer' => 'nullable|string|max:1000',
            'authorized_signature' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'show_authorized_signature' => 'boolean',
            'show_invoice_footer' => 'boolean',
        ]);

        $company = auth()->user()->company;
        $companyFields = collect($validated)->only(['invoice_prefix', 'invoice_start_number', 'payment_terms', 'invoice_footer'])->toArray();

        if ($request->hasFile('authorized_signature')) {
            if ($company->authorized_signature) {
                Storage::disk('public')->delete($company->authorized_signature);
            }
            $companyFields['authorized_signature'] = $request->file('authorized_signature')->store('signatures', 'public');
        }

        $company->update($companyFields);
        $this->settings()->update([
            'show_authorized_signature' => $request->boolean('show_authorized_signature'),
            'show_invoice_footer' => $request->boolean('show_invoice_footer'),
        ]);

        return back()->with('success', 'Invoice settings saved.');
    }

    public function updateQuotation(Request $request)
    {
        $validated = $request->validate([
            'quotation_prefix' => 'required|string|max:20|regex:/^[A-Za-z0-9_\-\/#.]*$/',
            'quotation_validity_days' => 'required|integer|min:0|max:365',
            'show_quotation_website' => 'boolean',
            'show_quotation_message' => 'boolean',
            'quotation_message' => 'nullable|string|max:1000',
        ]);

        auth()->user()->company->update([
            'quotation_prefix' => $validated['quotation_prefix'],
            'quotation_validity_days' => $validated['quotation_validity_days'],
        ]);

        $this->settings()->update([
            'quotation_message' => $validated['quotation_message'] ?? null,
            'show_quotation_website' => $request->boolean('show_quotation_website'),
            'show_quotation_message' => $request->boolean('show_quotation_message'),
        ]);

        return back()->with('success', 'Quotation settings saved.');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'date_format' => ['required', Rule::in(array_keys(CompanySetting::dateFormats()))],
            'timezone' => 'required|timezone:all',
            'tax_enabled' => 'boolean',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'email_notifications' => 'boolean',
            'invoice_due_reminder' => 'boolean',
            'overdue_reminder' => 'boolean',
            'show_logo_on_invoice' => 'boolean',
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Preferences saved.');
    }

    public function updatePayment(Request $request)
    {
        $validated = $request->validate([
            'show_payment_instructions' => 'boolean',
            'payment_instructions' => 'nullable|string|max:1000',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_routing_number' => 'nullable|string|max:100',
            'paypal_email' => 'nullable|email|max:255',
            'accept_bank_transfer' => 'boolean',
            'accept_cash' => 'boolean',
            'accept_stripe' => 'boolean',
            'payment_gateway_mode' => 'nullable|in:app,own',
            'stripe_public_key' => 'nullable|string|max:255|starts_with:pk_',
            'stripe_secret_key' => 'nullable|string|max:255|starts_with:sk_,rk_',
            'stripe_webhook_secret' => 'nullable|string|max:255|starts_with:whsec_',
        ]);

        foreach (['accept_bank_transfer', 'accept_cash', 'accept_stripe', 'show_payment_instructions'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }
        $validated['stripe_enabled'] = $validated['accept_stripe'];

        // Secrets are write-only: a blank field keeps the stored value.
        foreach (['stripe_secret_key', 'stripe_webhook_secret'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }

        $this->settings()->update($validated);

        return back()->with('success', 'Payment settings saved.');
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            // Hex only: these values are used in styles.
            'primary_color' => ['required', self::HEX],
            'secondary_color' => ['required', self::HEX],
            'sidebar_color' => ['required', self::HEX],
            'sidebar_text_color' => ['required', self::HEX],
            'invoice_template' => ['required', Rule::in(array_keys(CompanySetting::invoiceTemplates()))],
            'font_family' => ['required', Rule::in(array_keys(CompanySetting::fontFamilies()))],
            'font_size' => ['required', Rule::in(array_keys(CompanySetting::fontSizes()))],
            'heading_color' => ['nullable', self::HEX],
            'text_color' => ['nullable', self::HEX],
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Appearance saved.');
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string|max:255|starts_with:pk_',
            'stripe_secret_key' => 'nullable|string|max:255|starts_with:sk_,rk_',
            'stripe_webhook_secret' => 'nullable|string|max:255|starts_with:whsec_',
        ]);

        foreach (['stripe_secret_key', 'stripe_webhook_secret'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }

        $this->settings()->update($validated);

        return back()->with('success', 'Stripe settings saved.');
    }

    public function updateNotesTerms(Request $request)
    {
        $this->settings()->update($request->validate([
            'default_notes' => 'nullable|string|max:5000',
            'default_terms' => 'nullable|string|max:5000',
        ]));

        return back()->with('success', 'Default notes & terms saved.');
    }

    private function settings(): CompanySetting
    {
        $company = auth()->user()->company;

        return $company->settings ?? CompanySetting::create(['company_id' => $company->id]);
    }

    public function previewTemplate($template)
    {
        if (!array_key_exists($template, CompanySetting::invoiceTemplates())) {
            abort(404);
        }

        // Build the sample invoice from real (unsaved) models rather than stdClass, so
        // model accessors the templates rely on — bill_to_name, currency_symbol, etc. —
        // resolve exactly as they do for a genuine invoice. Nothing here is persisted.
        $settings = new CompanySetting();
        $settings->forceFill([
            'primary_color' => '#4f46e5',
            'show_authorized_signature' => false,
            'show_invoice_footer' => true,
        ]);

        $company = new Company();
        $company->forceFill([
            'name' => 'Acme Corp Inc.',
            'address' => '123 Business Avenue, Tech Park',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'email' => 'billing@acmecorp.com',
            'phone' => '+1 (555) 123-4567',
            'logo' => null,
            'currency' => 'USD',
            'tax_number' => 'US-123456789',
            'invoice_footer' => 'Thank you for your business!',
            'authorized_signature' => null,
        ]);
        $company->setRelation('settings', $settings);

        $client = new Client();
        $client->forceFill([
            'client_type' => Client::TYPE_COMPANY,
            'name' => 'John Doe',
            'company_name' => 'Doe Enterprises LLC',
            'address' => '456 Client Road',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94105',
            'country' => 'United States',
            'tax_number' => 'US-987654321',
            'email' => 'john@doe-enterprises.com',
        ]);

        $items = collect([
            ['name' => 'Web Development Services', 'description' => 'Frontend implementation of new dashboard', 'quantity' => 40, 'unit' => 'hrs', 'price' => 150.00, 'total' => 6000.00],
            ['name' => 'Hosting Setup', 'description' => 'Annual server configuration', 'quantity' => 1, 'unit' => 'yr', 'price' => 500.00, 'total' => 500.00],
            ['name' => 'Domain Registration', 'description' => 'Renewal of company domain', 'quantity' => 1, 'unit' => 'yr', 'price' => 50.00, 'total' => 50.00],
        ])->map(fn ($attributes) => (new InvoiceItem())->forceFill($attributes));

        $invoice = new Invoice();
        $invoice->forceFill([
            'id' => 123456, // Referenced by the Tech template
            'invoice_number' => 'INV-2024-001',
            'status' => Invoice::STATUS_SENT,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'notes' => 'Please pay within 14 days. Thank you for your continued business.',
            'terms' => 'Payment is due within 14 days.',
            'subtotal' => 6550.00,
            'tax_amount' => 655.00, // 10%
            'discount_amount' => 0,
            'discount_type' => 'fixed',
            'total' => 7205.00,
        ]);
        $invoice->setRelation('company', $company);
        $invoice->setRelation('client', $client);
        $invoice->setRelation('items', $items);

        return view('invoices.pdf.' . $template, compact('invoice'));
    }
}
