<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disposable Email Domains
    |--------------------------------------------------------------------------
    |
    | Registrations from these throwaway-inbox providers are rejected. Extend the
    | list via DISPOSABLE_EMAIL_DOMAINS (comma separated) without editing code.
    |
    */

    'disposable_email_domains' => array_values(array_filter(array_unique(array_merge([
        '10minutemail.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com',
        'mailinator.com', 'yopmail.com', 'yopmail.net', 'tempmail.com', 'temp-mail.org',
        'throwawaymail.com', 'trashmail.com', 'getnada.com', 'dispostable.com',
        'maildrop.cc', 'fakeinbox.com', 'mintemail.com', 'mohmal.com', 'emailondeck.com',
        'tempail.com', 'mailnesia.com', 'spamgourmet.com', 'moakt.com', 'tempr.email',
        'discard.email', 'burnermail.io', 'mailpoof.com', 'mytemp.email', 'tmpmail.org',
        'tmail.ws', 'grr.la', 'spam4.me', 'mail.tm', 'emailfake.com', 'inboxkitten.com',
    ], array_map('trim', explode(',', strtolower((string) env('DISPOSABLE_EMAIL_DOMAINS', '')))))))),

];
