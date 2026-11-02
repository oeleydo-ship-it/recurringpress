<?php

namespace App\Support;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Search-engine output for the public marketing site: head tags, robots.txt and sitemap.xml.
 * Everything here describes the platform's own public pages; tenant data never passes through it.
 */
class Seo
{
    private const SITEMAP_CACHE_KEY = 'seo_sitemap_entries';

    /** Published CMS pages with these slugs replace the built-in page at the same path (see LandingController). */
    private const CMS_OVERRIDES = ['about', 'contact'];

    /** Keep in step with the <Head> of each Marketing page; these tags are what crawlers see before any JS runs. */
    private const BUILT_IN_TITLES = [
        'Marketing/Features' => 'Features',
        'Marketing/Pricing' => 'Pricing',
        'Marketing/About' => 'About us',
        'Marketing/Faq' => 'Frequently asked questions',
        'Marketing/Contact' => 'Contact',
        'Marketing/Blog/Index' => 'Blog',
    ];

    private const ABOUT_DESCRIPTION = "We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use.";

    public static function defaultDescription(): ?string
    {
        return SystemSetting::get('seo_description', 'Professional invoicing software for freelancers and small businesses.');
    }

    public static function defaultKeywords(): ?string
    {
        return SystemSetting::get('seo_keywords', 'invoicing, billing, invoice software, freelancer invoicing');
    }

    public static function discourageIndexing(): bool
    {
        return filter_var(SystemSetting::get('seo_discourage_indexing'), FILTER_VALIDATE_BOOL);
    }

    /**
     * Bare verification code from whatever was pasted: the code, the full meta tag or the
     * "name=code" form. Null when it doesn't look like a code for $metaName.
     */
    public static function verificationCode(?string $input, string $metaName): ?string
    {
        $value = trim((string) $input);

        if (stripos($value, '<meta') !== false) {
            if (preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/i', $value, $name) && strcasecmp($name[1], $metaName) !== 0) {
                return null;
            }
            if (! preg_match('/\bcontent\s*=\s*["\']([^"\']*)["\']/i', $value, $content)) {
                return null;
            }
            $value = trim($content[1]);
        }

        $value = preg_replace('/^' . preg_quote($metaName, '/') . '=/i', '', $value);

        return preg_match('#^[A-Za-z0-9_\-+/=]{8,128}$#', $value) ? $value : null;
    }

    /**
     * The address this site is canonically reached at. The APP_URL host and its www. variant collapse
     * to APP_URL; other platform hosts (PLATFORM_HOSTS) keep their own address.
     */
    public static function baseUrl(Request $request): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = strtolower(preg_replace('/^www\./', '', (string) parse_url($appUrl, PHP_URL_HOST)));
        $host = strtolower(preg_replace('/^www\./', '', $request->getHost()));

        return $host === $appHost ? $appUrl : $request->getSchemeAndHttpHost();
    }

    public static function canonical(Request $request): string
    {
        $path = trim($request->path(), '/');

        foreach (self::CMS_OVERRIDES as $slug) {
            if ($path === "page/{$slug}") {
                $path = $slug;
            }
        }

        $url = self::baseUrl($request) . '/' . ($path === '' ? '' : implode('/', array_map('rawurlencode', explode('/', $path))));
        $pageNumber = $request->integer('page');

        return $path === 'blog' && $pageNumber > 1 ? "{$url}?page={$pageNumber}" : $url;
    }

    /**
     * Tags for a public marketing page, or null for every other page (those only get noindex).
     *
     * @param  array{component: string, props: array}  $page  The Inertia page object.
     */
    public static function forPage(array $page, Request $request): ?array
    {
        $component = $page['component'];

        if (! str_starts_with($component, 'Marketing/')) {
            return null;
        }

        $props = $page['props'];
        $site = SystemSetting::get('site_name', 'RecurringPress');
        $meta = self::pageMeta($component, $props);
        $title = $meta['title'] ? "{$meta['title']} · {$site}" : $site;
        $description = self::plainText($meta['description']) ?: self::plainText(self::defaultDescription());
        $canonical = self::canonical($request);
        $defaultImage = SystemSetting::get('seo_og_image');
        $image = $meta['image'] ?? ($defaultImage ? asset('storage/' . $defaultImage) : null);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => self::defaultKeywords(),
            'canonical' => $canonical,
            'robots' => self::discourageIndexing() ? 'noindex, nofollow' : null,
            'google_verification' => SystemSetting::get('seo_google_verification'),
            'bing_verification' => SystemSetting::get('seo_bing_verification'),
            'site_name' => $site,
            'og_title' => $meta['title'] ?: $site,
            'og_type' => $meta['type'],
            'image' => $image,
            'twitter_site' => SystemSetting::get('seo_twitter_site'),
            'published_at' => $meta['published_at'] ?? null,
            'modified_at' => $meta['modified_at'] ?? null,
            'json_ld' => self::jsonLd($component, $props, $request, $canonical, $site, $description, $image),
        ];
    }

    /** Non-marketing pages stay out of search; registration is the one public entry point worth finding. */
    public static function isIndexableAppPage(string $component): bool
    {
        return $component === 'Auth/Register' && ! self::discourageIndexing();
    }

    private static function pageMeta(string $component, array $props): array
    {
        $meta = ['title' => self::BUILT_IN_TITLES[$component] ?? null, 'description' => null, 'type' => 'website'];

        return match ($component) {
            'Marketing/Home' => [...$meta, 'title' => $props['hero']['title'] ?? null],
            'Marketing/Page' => [...$meta, 'title' => $props['page']['meta_title'] ?? null, 'description' => $props['page']['meta_description'] ?? null],
            'Marketing/Contact' => [...$meta, 'description' => $props['page']['meta_description'] ?? null],
            'Marketing/About' => [...$meta, 'description' => self::ABOUT_DESCRIPTION],
            'Marketing/Features', 'Marketing/Pricing' => [...$meta, 'description' => $props['subtitle'] ?? null],
            'Marketing/Blog/Show' => [
                ...$meta,
                'title' => $props['post']['meta_title'] ?? null,
                'description' => $props['post']['meta_description'] ?? null,
                'type' => 'article',
                'image' => $props['post']['image'] ?? null,
                'published_at' => $props['post']['published_at'] ?? null,
                'modified_at' => $props['post']['updated_at'] ?? null,
            ],
            default => $meta,
        };
    }

    private static function plainText(?string $text): string
    {
        return Str::squish(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function jsonLd(string $component, array $props, Request $request, string $canonical, string $site, string $description, ?string $image): ?string
    {
        $root = self::baseUrl($request) . '/';
        $logo = ($logoPath = SystemSetting::get('site_logo')) ? asset('storage/' . $logoPath) : null;
        $organization = array_filter(['@type' => 'Organization', 'name' => $site, 'url' => $root, 'logo' => $logo]);

        $data = match ($component) {
            'Marketing/Home' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $organization,
                    array_filter(['@type' => 'WebSite', 'name' => $site, 'url' => $root, 'description' => $description]),
                    array_filter(['@type' => 'SoftwareApplication', 'name' => $site, 'url' => $root, 'description' => $description, 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web']),
                ],
            ],
            'Marketing/Blog/Show' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $props['post']['title'] ?? null,
                'description' => $description,
                'url' => $canonical,
                'mainEntityOfPage' => $canonical,
                'image' => $image,
                'datePublished' => $props['post']['published_at'] ?? null,
                'dateModified' => $props['post']['updated_at'] ?? null,
                'author' => ! empty($props['post']['author']) ? ['@type' => 'Person', 'name' => $props['post']['author']] : null,
                'publisher' => $organization,
            ]),
            default => null,
        };

        // JSON_HEX_TAG keeps admin-authored text from closing the <script> element.
        return $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) : null;
    }

    /* ------------------------------ robots.txt ------------------------------ */

    /**
     * Blocks the app, not public links: /invoice and /quotation client links are left crawlable on
     * purpose, because a crawler has to fetch them to see their noindex (a blocked URL can still be
     * indexed from a link elsewhere). The same goes for /login and the password pages.
     */
    public static function defaultRobotsRules(): string
    {
        $private = [
            'superadmin', 'horizon', 'install', 'auto-install', 'onboarding', 'dashboard', 'clients', 'company', 'email',
            'expenses', 'invoices', 'notifications', 'payments', 'payouts', 'products', 'profile', 'quotations',
            'receipts', 'recurring-invoices', 'reports', 'settings', 'users',
        ];

        return "User-agent: *\nAllow: /\n" . implode("\n", array_map(fn ($path) => "Disallow: /{$path}", $private));
    }

    public static function robotsTxt(Request $request): string
    {
        if (self::discourageIndexing()) {
            return "User-agent: *\nDisallow: /\n";
        }

        $rules = trim(str_replace(["\r\n", "\r"], "\n", (string) SystemSetting::get('seo_robots_txt'))) ?: self::defaultRobotsRules();

        if (! preg_match('/^\s*sitemap\s*:/mi', $rules)) {
            $rules .= "\n\nSitemap: " . self::baseUrl($request) . '/sitemap.xml';
        }

        return $rules . "\n";
    }

    /** Tenant hosts carry only client documents: nothing to crawl and no platform sitemap to advertise. */
    public static function tenantRobotsTxt(): string
    {
        return "User-agent: *\nDisallow: /\n";
    }

    /* ------------------------------ sitemap.xml ------------------------------ */

    public static function sitemapXml(Request $request): string
    {
        $base = self::baseUrl($request);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::discourageIndexing() ? [] : self::sitemapEntries() as $path => $lastmod) {
            $xml .= '  <url><loc>' . htmlspecialchars("{$base}/{$path}", ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            $xml .= ($lastmod ? "<lastmod>{$lastmod}</lastmod>" : '') . "</url>\n";
        }

        return $xml . "</urlset>\n";
    }

    public static function forgetSitemap(): void
    {
        Cache::forget(self::SITEMAP_CACHE_KEY);
    }

    /**
     * Path => lastmod (or null) for every public URL. Plain strings only, so the cached value holds
     * no models. The hour's TTL also picks up scheduled posts as they go live.
     *
     * @return array<string, ?string>
     */
    private static function sitemapEntries(): array
    {
        return Cache::remember(self::SITEMAP_CACHE_KEY, 3600, function () {
            $pages = CmsPage::published()->get(['slug', 'updated_at']);
            $posts = CmsPost::published()
                ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderByDesc('published_at')
                ->get(['slug', 'published_at', 'updated_at']);

            $postDates = $posts->mapWithKeys(fn (CmsPost $post) => [$post->slug => self::latest($post->updated_at, $post->published_at)]);
            $override = fn (string $slug) => self::latest($pages->firstWhere('slug', $slug)?->updated_at);
            $blogLastmod = $postDates->filter()->sortDesc()->first();

            $entries = [
                '' => self::latest(SystemSetting::where('key', 'like', 'landing\_%')->max('updated_at')),
                'features' => null,
                'pricing' => null,
                'about' => $override('about'),
                'contact' => $override('contact'),
                'faq' => null,
                'blog' => $blogLastmod,
            ];

            foreach ($pages as $page) {
                if (! in_array($page->slug, self::CMS_OVERRIDES, true)) {
                    $entries['page/' . rawurlencode($page->slug)] = self::latest($page->updated_at);
                }
            }

            foreach ($postDates as $slug => $lastmod) {
                $entries['blog/' . rawurlencode($slug)] = $lastmod;
            }

            return $entries;
        });
    }

    /** The most recent of the given dates in W3C format, or null when there are none. */
    private static function latest(mixed ...$dates): ?string
    {
        $dates = array_map(fn ($date) => Carbon::parse($date), array_filter($dates));

        return $dates ? max($dates)->toAtomString() : null;
    }
}
