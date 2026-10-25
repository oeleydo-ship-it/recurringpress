<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Initial Superadmin (Git / auto deployments)
    |--------------------------------------------------------------------------
    |
    | When both are set and no superadmin exists yet, `php artisan migrate` and
    | `php artisan db:seed` create the superadmin with this login. An existing
    | superadmin is never changed, so the password can be removed from the
    | environment after the first deploy and changed under "Your account".
    |
    */
    'email' => env('SUPERADMIN_EMAIL'),
    'password' => env('SUPERADMIN_PASSWORD'),
    'name' => env('SUPERADMIN_NAME', 'Super Admin'),
];
