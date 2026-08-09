<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded super_admin credentials
    |--------------------------------------------------------------------------
    |
    | DatabaseSeeder uses these to create (or update) the initial super_admin
    | account. Set ADMIN_EMAIL/ADMIN_PASSWORD in production so the seeded
    | account never uses the well-known local-dev default password.
    |
    */

    'email' => env('ADMIN_EMAIL', 'admin@pingmasters.test'),
    'password' => env('ADMIN_PASSWORD', 'password'),

];
