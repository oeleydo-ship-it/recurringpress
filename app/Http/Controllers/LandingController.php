<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Marketing/Home', [
            'hero' => [
                'eyebrow' => SystemSetting::get('landing_hero_eyebrow', 'Invoicing for freelancers & small businesses'),
                'title' => SystemSetting::get('landing_hero_title', 'Professional Invoicing Made Simple'),
                'subtitle' => SystemSetting::get('landing_hero_subtitle', 'Create beautiful invoices, manage clients, track payments, and grow your business. Built for freelancers and small businesses.'),
                'cta_text' => SystemSetting::get('landing_cta_text', 'Get Started Free'),
                'cta_url' => $this->safeUrl(SystemSetting::get('landing_cta_url'), route('register')),
            ],
            'trust' => [
                'text' => SystemSetting::get('landing_trust_text', 'Trusted by thousands of freelancers and businesses'),
                'badges' => $this->json('landing_trust_badges_json'),
            ],
            'features' => [
                'title' => SystemSetting::get('landing_features_title', 'Everything you need to get paid'),
                'subtitle' => SystemSetting::get('landing_features_subtitle', 'Powerful features to streamline your invoicing workflow'),
                'items' => $this->json('landing_features_json'),
            ],
            'pricing' => [
                'title' => SystemSetting::get('landing_pricing_title', 'Simple, transparent pricing'),
                'subtitle' => SystemSetting::get('landing_pricing_subtitle', "Start free, upgrade when you're ready"),
                'plans' => $this->plans(),
            ],
            'testimonials' => [
                'title' => SystemSetting::get('landing_testimonials_title', 'Loved by freelancers worldwide'),
                'subtitle' => SystemSetting::get('landing_testimonials_subtitle', 'See what our customers have to say'),
                'items' => $this->json('landing_testimonials_json'),
            ],
        ]);
    }

    public function features()
    {
        return Inertia::render('Marketing/Features', [
            'title' => SystemSetting::get('features_page_title', 'Powerful features for modern businesses'),
            'subtitle' => SystemSetting::get('features_page_subtitle', 'Everything you need to invoice clients, get paid and stay organised.'),
            'items' => $this->json('features_page_json') ?: $this->json('landing_features_json'),
            'cta' => [
                'title' => SystemSetting::get('features_page_cta_title', 'Ready to get started?'),
                'subtitle' => str_replace('{site_name}', SystemSetting::get('site_name', 'RecurringPress'), (string) SystemSetting::get('features_page_cta_subtitle', 'Create your free account in under a minute.')),
            ],
        ]);
    }

    public function pricing()
    {
        return Inertia::render('Marketing/Pricing', [
            'title' => SystemSetting::get('landing_pricing_title', 'Simple, transparent pricing'),
            'subtitle' => SystemSetting::get('landing_pricing_subtitle', "Start free, upgrade when you're ready"),
            'plans' => $this->plans(),
            'faq' => array_slice(config('marketing.faq.1.items', []), 0, 3),
        ]);
    }

    public function about()
    {
        // A published CMS page with this slug overrides the built-in page.
        if ($page = CmsPage::where('slug', 'about')->where('is_published', true)->first()) {
            return $this->renderCmsPage($page);
        }

        return Inertia::render('Marketing/About');
    }

    public function contact()
    {
        return Inertia::render('Marketing/Contact', [
            'page' => ($page = CmsPage::where('slug', 'contact')->where('is_published', true)->first()) ? $this->cmsPayload($page) : null,
            'email' => SystemSetting::get('contact_email') ?: SystemSetting::get('mail_from_address'),
        ]);
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc,filter|max:255',
            'subject' => ['required', 'string', 'max:200', 'not_regex:/[\r\n]/'],
            'message' => 'required|string|min:10|max:5000',
        ]);

        $to = SystemSetting::get('contact_email') ?: SystemSetting::get('mail_from_address') ?: 'support@recurringpress.com';

        try {
            \Illuminate\Support\Facades\Mail::to($to)
                ->queue((new \App\Mail\ContactFormMail(
                    $request->name,
                    $request->email,
                    $request->subject,
                    $request->message
                ))->onQueue('mail'));
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
        }

        return back()->with('success', "Thanks for your message! We'll get back to you soon.");
    }

    public function faq()
    {
        $site = SystemSetting::get('site_name', 'RecurringPress');

        return Inertia::render('Marketing/Faq', [
            'groups' => collect(config('marketing.faq', []))->map(fn ($g) => [
                'label' => $g['label'],
                'items' => collect($g['items'])->map(fn ($i) => [
                    'q' => str_replace(['RecurringPress', '{{site}}'], $site, $i['q']),
                    'a' => str_replace(['RecurringPress', '{{site}}'], $site, $i['a']),
                ])->all(),
            ])->all(),
        ]);
    }

    /** Public CMS page (/page/{slug}). */
    public function page(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return $this->renderCmsPage($page);
    }

    /* ------------------------------------------------------------------ */

    private function renderCmsPage(CmsPage $page)
    {
        return Inertia::render('Marketing/Page', ['page' => $this->cmsPayload($page)]);
    }

    private function cmsPayload(CmsPage $page): array
    {
        $blocks = $page->editor_mode === 'blocks' && ! empty($page->blocks);

        return [
            'title' => $page->title,
            'show_title' => $page->show_title === null ? true : (bool) $page->show_title,
            'full_width' => $blocks,
            // Superadmin-authored HTML (same trust model as before). Block pages are rendered
            // server-side by the existing Blade block renderer.
            'content' => $blocks ? view('partials.block-renderer', ['blocks' => $page->blocks])->render() : $page->content,
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description ?: Str::limit(trim(strip_tags((string) $page->content)), 160),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }

    private function plans(): array
    {
        return SubscriptionPlan::active()->ordered()->get()->map(fn ($p) => [
            ...$p->only('id', 'name', 'slug', 'description', 'billing_cycle', 'trial_days', 'max_invoices', 'max_clients', 'max_users', 'max_products', 'custom_branding', 'white_label', 'custom_domain', 'subdomain', 'stripe_enabled', 'is_popular'),
            'price' => (float) $p->price,
            'features' => $p->features ?? [],
        ])->all();
    }

    private function json(string $key): array
    {
        $decoded = json_decode((string) SystemSetting::get($key), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** Only http(s) or relative URLs for admin-configured links. */
    private function safeUrl(?string $url, string $fallback): string
    {
        if (! $url) {
            return $fallback;
        }

        return (str_starts_with($url, '/') && ! str_starts_with($url, '//')) || preg_match('#^https?://#i', $url) ? $url : $fallback;
    }
}
