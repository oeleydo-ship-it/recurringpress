<?php

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [

    /*
    | Hosts that serve the full platform (landing, app, superadmin). The APP_URL host and its
    | www. variant are always included, as are localhost and bare IP addresses. Any other Host
    | header must be a tenant's verified custom domain, or the request gets a plain 404.
    */
    'platform_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('PLATFORM_HOSTS', ''))))),

    'custom_domains' => [
        // Off until the server can serve TLS for tenant domains (see INSTALL.md, "Custom
        // domains"). While off, the settings tab explains it's unavailable, links use the
        // platform address and custom hosts get a 404.
        'enabled' => (bool) env('CUSTOM_DOMAINS_ENABLED', false),

        // What tenants point their CNAME at (shown in the DNS instructions).
        'cname_target' => env('CUSTOM_DOMAIN_CNAME_TARGET', $appHost),

        // A-record targets for apex domains. Defaults to whatever cname_target resolves to.
        'ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('CUSTOM_DOMAIN_IPS', ''))))),

        // TXT ownership record: <prefix>.<domain> = "recurringpress-verify=<token>".
        'txt_prefix' => '_recurringpress',

        // Scheme used when building links on a custom domain. Requires a TLS certificate for
        // each domain on the server (see INSTALL.md, "Custom domains").
        'scheme' => env('CUSTOM_DOMAIN_SCHEME', 'https'),
    ],

    'smtp' => [
        // Block tenant SMTP hosts on private/loopback networks (SSRF to Redis, MySQL, metadata
        // endpoints...). Off by default outside production so a local Mailpit works.
        'block_private_hosts' => (bool) env('TENANT_SMTP_BLOCK_PRIVATE', env('APP_ENV') === 'production'),

        'allowed_ports' => [25, 465, 587, 2525],

        'timeout' => 15,
    ],
];
