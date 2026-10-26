<?php

/*
 * Controls which named routes are exposed to the browser for the JS route() helper
 * (see HandleInertiaRequests). Tenant/public pages never learn superadmin, Horizon or
 * installer URLs. Patterns prefixed with "!" are exclusions.
 */
$internal = ['!install.*', '!autoinstall.*', '!horizon.*', '!stripe.webhook', '!storage.*', '!sanctum.*', '!ignition.*'];

return [
    'groups' => [
        'app' => ['*', '!superadmin.*', ...$internal],
        'superadmin' => ['superadmin.*', 'landing', 'horizon.index'],
    ],
];
