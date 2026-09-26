<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);
        $currencies = CompanySetting::currencies();
        $dateFormats = CompanySetting::dateFormats();
        $invoiceTemplates = CompanySetting::invoiceTemplates();
        $colorPresets = CompanySetting::colorPresets();
        $fontFamilies = CompanySetting::fontFamilies();
        $fontSizes = CompanySetting::fontSizes();

        return view('settings.index', compact('company', 'settings', 'currencies', 'dateFormats', 'invoiceTemplates', 'colorPresets', 'fontFamilies', 'fontSizes'));
    }

    public function updateCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'currency' => 'required|string|max:10',
            'logo' => 'nullable|image|max:2048',
        ]);

        $company = auth()->user()->company;

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($validated);

        return back()->with('success', 'Company settings updated successfully.');
    }

    public function updateInvoice(Request $request)
    {
        $validated = $request->validate([
            'invoice_prefix' => 'required|string|max:20',
            'invoice_start_number' => 'required|integer|min:1',
            'payment_terms' => 'required|integer|min:0|max:365',
            'invoice_footer' => 'nullable|string|max:1000',
            'authorized_signature' => 'nullable|image|max:2048',
            'show_authorized_signature' => 'boolean',
            'show_invoice_footer' => 'boolean',
        ]);

        $company = auth()->user()->company;

        $companyFields = collect($validated)->only([
            'invoice_prefix', 'invoice_start_number', 'payment_terms', 'invoice_footer',
        ])->toArray();

        if ($request->hasFile('authorized_signature')) {
            if ($company->authorized_signature) {
                Storage::disk('public')->delete($company->authorized_signature);
            }
            $companyFields['authorized_signature'] = $request->file('authorized_signature')->store('signatures', 'public');
        }

        $company->update($companyFields);

        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);
        $settings->update([
            'show_authorized_signature' => $request->boolean('show_authorized_signature'),
            'show_invoice_footer' => $request->boolean('show_invoice_footer'),
        ]);

        return back()->with('success', 'Invoice settings updated successfully.');
    }

    public function updateQuotation(Request $request)
    {
        $validated = $request->validate([
            'quotation_prefix' => 'required|string|max:20',
            'quotation_validity_days' => 'required|integer|min:0|max:365',
            'show_quotation_website' => 'boolean',
            'show_quotation_message' => 'boolean',
            'quotation_message' => 'nullable|string|max:1000',
        ]);

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $company->update([
            'quotation_prefix' => $validated['quotation_prefix'],
            'quotation_validity_days' => $validated['quotation_validity_days'],
        ]);

        $settingsFields = collect($validated)->except(['quotation_prefix', 'quotation_validity_days'])->toArray();
        $settingsFields['show_quotation_website'] = $request->boolean('show_quotation_website');
        $settingsFields['show_quotation_message'] = $request->boolean('show_quotation_message');

        $settings->update($settingsFields);

        return back()->with('success', 'Quotation settings updated successfully.');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'date_format' => 'required|string',
            'timezone' => 'required|string',
            'tax_enabled' => 'boolean',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'email_notifications' => 'boolean',
            'invoice_due_reminder' => 'boolean',
            'overdue_reminder' => 'boolean',
            'theme_color' => 'nullable|string|max:20',
            'show_logo_on_invoice' => 'boolean',
        ]);

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $settings->update($validated);

        return back()->with('success', 'Preferences updated successfully.');
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
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
        ]);

        $validated['accept_bank_transfer'] = $request->boolean('accept_bank_transfer');
        $validated['accept_cash'] = $request->boolean('accept_cash');
        $validated['accept_stripe'] = $request->boolean('accept_stripe');

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $settings->update($validated);

        return back()->with('success', 'Payment settings updated successfully.');
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'sidebar_color' => 'required|string|max:7',
            'sidebar_text_color' => 'required|string|max:7',
            'invoice_template' => 'required|string|in:classic,modern,minimal,corporate,elegant,creative,professional,simple,bold,striped,tech,luxe',
            'font_family' => 'required|string|in:Inter,Poppins,Roboto,Open Sans,Lato,Nunito',
            'font_size' => 'required|string|in:small,base,large',
            'heading_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
        ]);

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $settings->update($validated);

        return back()->with('success', 'Theme settings updated successfully.');
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
        ]);

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $settings->update($validated);

        return back()->with('success', 'Stripe settings updated successfully.');
    }

    public function updateNotesTerms(Request $request)
    {
        $validated = $request->validate([
            'default_notes' => 'nullable|string',
            'default_terms' => 'nullable|string',
        ]);

        $company = auth()->user()->company;
        $settings = $company->settings ?? CompanySetting::create(['company_id' => $company->id]);

        $settings->update($validated);

        return back()->with('success', 'Notes & Terms settings updated successfully.');
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
