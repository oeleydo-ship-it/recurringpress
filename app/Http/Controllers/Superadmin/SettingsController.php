<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function general()
    {
        $settings = SystemSetting::getMany([
            'site_name' => 'InvoiceApp',
            'site_tagline' => 'Professional Invoicing Made Simple',
            'site_logo' => null,
            'site_favicon' => null,
            'support_email' => 'support@invoiceapp.com',
            'footer_text' => null,
            'hide_site_name' => false,
            'email_verification_enabled' => false,
            'google_analytics_script' => null,
            'livechat_script' => null,
            // SEO Settings
            'seo_description' => null,
            'seo_keywords' => null,
            'seo_og_image' => null,
        ]);

        return view('superadmin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'footer_text' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|max:2048',
            'site_favicon' => 'nullable|image|max:512',
            'hide_site_name' => 'boolean',
            'email_verification_enabled' => 'boolean',
            'google_analytics_script' => 'nullable|string',
            'livechat_script' => 'nullable|string',
            // SEO Settings
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|image|max:2048',
        ]);

        // Handle checkboxes
        $validated['hide_site_name'] = $request->boolean('hide_site_name') ? '1' : '0';
        $validated['email_verification_enabled'] = $request->boolean('email_verification_enabled') ? '1' : '0';

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            $oldLogo = SystemSetting::get('site_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $validated['site_logo'] = $request->file('site_logo')->store('branding', 'public');
        } else {
            unset($validated['site_logo']);
        }

        // Handle favicon upload
        if ($request->hasFile('site_favicon')) {
            $oldFavicon = SystemSetting::get('site_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $validated['site_favicon'] = $request->file('site_favicon')->store('branding', 'public');
        } else {
            unset($validated['site_favicon']);
        }

        // Handle OG image upload
        if ($request->hasFile('seo_og_image')) {
            $oldOgImage = SystemSetting::get('seo_og_image');
            if ($oldOgImage) {
                Storage::disk('public')->delete($oldOgImage);
            }
            $validated['seo_og_image'] = $request->file('seo_og_image')->store('branding', 'public');
        } else {
            unset($validated['seo_og_image']);
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'General settings updated successfully.');
    }

    public function stripe()
    {
        $settings = SystemSetting::getMany([
            'stripe_enabled' => false,
            'stripe_mode' => 'test',
            'stripe_test_publishable_key' => null,
            'stripe_test_secret_key' => null,
            'stripe_live_publishable_key' => null,
            'stripe_live_secret_key' => null,
            'stripe_webhook_secret' => null,
        ]);

        return view('superadmin.settings.stripe', compact('settings'));
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_enabled' => 'boolean',
            'stripe_mode' => 'required|in:test,live',
            'stripe_test_publishable_key' => 'nullable|string',
            'stripe_test_secret_key' => 'nullable|string',
            'stripe_live_publishable_key' => 'nullable|string',
            'stripe_live_secret_key' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
        ]);

        $validated['stripe_enabled'] = $request->boolean('stripe_enabled') ? '1' : '0';

        SystemSetting::setMany($validated);

        return back()->with('success', 'Stripe settings updated successfully.');
    }

    public function smtp()
    {
        $settings = SystemSetting::getMany([
            'mail_mailer' => 'smtp',
            'mail_host' => null,
            'mail_port' => '587',
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
            'mail_from_address' => null,
            'mail_from_name' => config('app.name'),
        ]);

        return view('superadmin.settings.smtp', compact('settings'));
    }

    public function updateSmtp(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|in:smtp,sendmail,mailgun,ses,postmark,log,array',
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string|in:ssl,tls,null',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        // If encryption is "null" (string) from select, save as actual null
        if ($validated['mail_encryption'] === 'null') {
            $validated['mail_encryption'] = null;
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'SMTP settings updated successfully.');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Force reload settings to ensure we use the latest from DB
            $settings = SystemSetting::getMany([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name'
            ]);

            if (!empty($settings['mail_host'])) {
                config([
                    'mail.default' => $settings['mail_mailer'] ?? 'smtp',
                    'mail.mailers.smtp.host' => $settings['mail_host'],
                    'mail.mailers.smtp.port' => $settings['mail_port'],
                    'mail.mailers.smtp.username' => $settings['mail_username'],
                    'mail.mailers.smtp.password' => $settings['mail_password'],
                    'mail.mailers.smtp.encryption' => $settings['mail_encryption'],
                    'mail.from.address' => $settings['mail_from_address'],
                    'mail.from.name' => $settings['mail_from_name'],
                ]);
            }

            // Re-resolve mailer to ensure it uses new config
            \Illuminate\Support\Facades\Mail::purge('smtp');

            \Illuminate\Support\Facades\Mail::to($request->test_email)->send(new \App\Mail\TestMail());
            return back()->with('success', 'Test email sent successfully to ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage() .
                ' | Host: ' . config('mail.mailers.smtp.host') .
                ' | Port: ' . config('mail.mailers.smtp.port'));
        }
    }

    public function payouts()
    {
        $settings = SystemSetting::getMany([
            'payout_fee_percentage' => 0,
            'payout_minimum_amount' => 50,
        ]);

        return view('superadmin.settings.payouts', compact('settings'));
    }

    public function updatePayouts(Request $request)
    {
        $validated = $request->validate([
            'payout_fee_percentage' => 'required|numeric|min:0|max:100',
            'payout_minimum_amount' => 'required|numeric|min:0',
        ]);

        SystemSetting::setMany($validated);

        return back()->with('success', 'Payout settings updated successfully.');
    }

    public function landing()
    {
        // Default features JSON (for landing homepage)
        $defaultFeatures = json_encode([
            ['icon' => 'fas fa-file-invoice', 'color' => 'sky', 'title' => 'Professional Invoices', 'description' => 'Create stunning invoices in seconds with customizable templates that match your brand.'],
            ['icon' => 'fas fa-file-alt', 'color' => 'green', 'title' => 'Quotations & Estimates', 'description' => 'Send professional quotes and convert them to invoices with a single click.'],
            ['icon' => 'fab fa-stripe', 'color' => 'purple', 'title' => 'Online Payments', 'description' => 'Accept payments via Stripe. Let clients pay directly from the invoice.'],
            ['icon' => 'fas fa-users', 'color' => 'blue', 'title' => 'Client Management', 'description' => 'Keep all your client information organized and easily accessible.'],
            ['icon' => 'fas fa-chart-line', 'color' => 'orange', 'title' => 'Reports & Analytics', 'description' => 'Track your revenue, outstanding payments, and business growth.'],
            ['icon' => 'fas fa-palette', 'color' => 'pink', 'title' => 'Custom Branding', 'description' => 'Customize colors, templates, and add your logo to match your brand identity.'],
        ]);

        // Default features page JSON (with bullet points for the /features page)
        $defaultFeaturesPage = json_encode([
            ['icon' => 'fas fa-file-invoice', 'color' => 'sky', 'title' => 'Professional Invoices', 'description' => 'Create stunning invoices in seconds with customizable templates that match your brand.', 'bullets' => ['Multiple templates', 'Custom branding', 'PDF downloads']],
            ['icon' => 'fas fa-file-alt', 'color' => 'green', 'title' => 'Quotations & Estimates', 'description' => 'Send professional quotes and convert them to invoices with a single click.', 'bullets' => ['One-click conversion', 'Validity tracking', 'Client approval']],
            ['icon' => 'fab fa-stripe', 'color' => 'purple', 'title' => 'Online Payments', 'description' => 'Accept payments via Stripe. Let clients pay directly from the invoice.', 'bullets' => ['Credit cards', 'Automatic receipts', 'Payment tracking']],
            ['icon' => 'fas fa-users', 'color' => 'blue', 'title' => 'Client Management', 'description' => 'Keep all your client information organized and easily accessible.', 'bullets' => ['Contact details', 'Invoice history', 'Notes & tags']],
            ['icon' => 'fas fa-chart-line', 'color' => 'orange', 'title' => 'Reports & Analytics', 'description' => 'Track your revenue, outstanding payments, and business growth.', 'bullets' => ['Revenue reports', 'Payment tracking', 'Dashboard insights']],
            ['icon' => 'fas fa-users-cog', 'color' => 'pink', 'title' => 'Team Collaboration', 'description' => 'Invite team members and work together on invoices and clients.', 'bullets' => ['Multiple users', 'Role permissions', 'Activity logs']],
        ]);

        // Default testimonials JSON
        $defaultTestimonials = json_encode([
            ['name' => 'Sarah Johnson', 'role' => 'Freelance Designer', 'initials' => 'SJ', 'color' => 'sky', 'quote' => 'InvoiceApp has transformed how I handle billing. I get paid faster and spend less time on admin work.'],
            ['name' => 'Mike Chen', 'role' => 'Web Developer', 'initials' => 'MC', 'color' => 'green', 'quote' => 'The quotation to invoice feature is a game changer. My conversion rate has increased by 40%.'],
            ['name' => 'Emily Parker', 'role' => 'Marketing Consultant', 'initials' => 'EP', 'color' => 'purple', 'quote' => 'Clean, professional invoices that my clients love. The Stripe integration makes payment seamless.'],
        ]);

        // Default trust badges JSON
        $defaultTrustBadges = json_encode([
            ['name' => 'TechCorp'],
            ['name' => 'StartupX'],
            ['name' => 'DesignCo'],
            ['name' => 'DevStudio'],
            ['name' => 'CreativeHub'],
        ]);

        $settings = SystemSetting::getMany([
            // Hero Section
            'landing_hero_title' => 'Professional Invoicing Made Simple',
            'landing_hero_subtitle' => 'Create beautiful invoices, manage clients, track payments, and grow your business. Built for freelancers and small businesses who want to get paid faster.',
            'landing_cta_text' => 'Get Started Free',
            'landing_cta_url' => route('register'),
            // Trust Badges Section
            'landing_trust_text' => 'Trusted by thousands of freelancers and businesses',
            'landing_trust_badges_json' => $defaultTrustBadges,
            // Features Section (homepage)
            'landing_features_title' => 'Everything you need to get paid',
            'landing_features_subtitle' => 'Powerful features to streamline your invoicing workflow',
            'landing_features_json' => $defaultFeatures,
            // Features Page (dedicated /features page)
            'features_page_title' => 'Powerful Features for Your Business',
            'features_page_subtitle' => 'Everything you need to create professional invoices, manage clients, and get paid faster.',
            'features_page_json' => $defaultFeaturesPage,
            'features_page_cta_title' => 'Ready to get started?',
            'features_page_cta_subtitle' => 'Try {site_name} with our free plan. No credit card required.',
            // Pricing Section
            'landing_pricing_title' => 'Simple, transparent pricing',
            'landing_pricing_subtitle' => 'Start free, upgrade when you\'re ready',
            // Testimonials Section
            'landing_testimonials_title' => 'Loved by freelancers worldwide',
            'landing_testimonials_subtitle' => 'See what our customers have to say',
            'landing_testimonials_json' => $defaultTestimonials,
            // Footer
            'landing_footer_text' => 'Professional invoicing software for freelancers and small businesses.',
        ]);

        return view('superadmin.settings.landing', compact('settings'));
    }

    public function updateLanding(Request $request)
    {
        $validated = $request->validate([
            // Hero
            'landing_hero_title' => 'required|string|max:255',
            'landing_hero_subtitle' => 'required|string|max:1000',
            'landing_cta_text' => 'required|string|max:50',
            'landing_cta_url' => 'required|string|max:255',
            // Features (homepage)
            'landing_features_title' => 'required|string|max:255',
            'landing_features_subtitle' => 'required|string|max:255',
            'landing_features_json' => 'nullable|string',
            // Features Page (dedicated /features page)
            'features_page_title' => 'nullable|string|max:255',
            'features_page_subtitle' => 'nullable|string|max:500',
            'features_page_json' => 'nullable|string',
            'features_page_cta_title' => 'nullable|string|max:255',
            'features_page_cta_subtitle' => 'nullable|string|max:500',
            // Trust Badges
            'landing_trust_text' => 'nullable|string|max:255',
            'landing_trust_badges_json' => 'nullable|string',
            // Pricing
            'landing_pricing_title' => 'required|string|max:255',
            'landing_pricing_subtitle' => 'required|string|max:255',
            // Testimonials
            'landing_testimonials_title' => 'required|string|max:255',
            'landing_testimonials_subtitle' => 'required|string|max:255',
            'landing_testimonials_json' => 'nullable|string',
            // Footer
            'landing_footer_text' => 'nullable|string|max:500',
        ]);

        SystemSetting::setMany($validated);

        return back()->with('success', 'Landing page settings updated successfully.');
    }
}
