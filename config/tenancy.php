<?php

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [

    /*
    | Hosts that serve the full platform (landing, app, superadmin). The APP_URL host and its
    | www. variant are always included, as are localhost and bare IP addresses. While custom
    | domains or subdomains are switched on (Superadmin → Custom domains), any other Host
    | header must belong to a tenant, or the request gets a plain 404.
    */
    'platform_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('PLATFORM_HOSTS', ''))))),

    // Tenant custom domains are Cloudflare for SaaS custom hostnames. The zone, API token,
    // CNAME target and on/off switch are set in Superadmin → Custom domains.
    'cloudflare' => [
        'api_base' => env('CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4'),
        'timeout' => 15,
    ],

    'custom_domains' => [
        // Scheme for links on a tenant's custom domain (Cloudflare terminates TLS).
        'scheme' => env('CUSTOM_DOMAIN_SCHEME', 'https'),
    ],

    'subdomains' => [
        // <slug>.<base>. Needs a proxied wildcard DNS record (*.<base>) on Cloudflare, whose
        // Universal SSL covers one level of subdomains (see INSTALL.md, "Tenant subdomains").
        'base' => env('TENANT_SUBDOMAIN_BASE', preg_replace('/^www\./', '', $appHost)),

        'scheme' => env('TENANT_SUBDOMAIN_SCHEME', 'https'),

        // Slugs nobody can claim (infrastructure, auth and brand-confusing names).
        'reserved' => [
            'www', 'app', 'admin', 'administrator', 'superadmin', 'api', 'mail', 'email', 'smtp', 'imap', 'pop', 'pop3',
            'mx', 'ns', 'ns1', 'ns2', 'dns', 'ftp', 'sftp', 'ssh', 'vpn', 'cdn', 'static', 'assets', 'media', 'files',
            'img', 'images', 'storage', 'status', 'billing', 'pay', 'payment', 'payments', 'checkout', 'invoice',
            'invoices', 'quotation', 'quotations', 'account', 'accounts', 'login', 'logout', 'signin', 'signup',
            'register', 'auth', 'oauth', 'sso', 'secure', 'security', 'support', 'help', 'docs', 'blog', 'news',
            'dev', 'staging', 'stage', 'test', 'testing', 'demo', 'beta', 'preview', 'sandbox', 'local', 'localhost',
            'dashboard', 'horizon', 'webhook', 'webhooks', 'stripe', 'root', 'system', 'internal', 'recurringpress',
            'customers', 'custom', 'origin', 'fallback', 'proxy', 'edge',
        ],
    ],

    'smtp' => [
        // Block tenant SMTP hosts on private/loopback networks (SSRF to Redis, MySQL, metadata
        // endpoints...). Off by default outside production so a local Mailpit works.
        'block_private_hosts' => (bool) env('TENANT_SMTP_BLOCK_PRIVATE', env('APP_ENV') === 'production'),

        'allowed_ports' => [25, 465, 587, 2525],

        'timeout' => 15,
    ],
];
