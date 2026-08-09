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

    /*
    |--------------------------------------------------------------------------
    | Default password for admin-initiated resets
    |--------------------------------------------------------------------------
    |
    | Used by Admin\UserController::resetPassword() when a super_admin resets
    | someone's password from the Usuarios panel. The user must change it on
    | their next visit to Settings — the admin sees this value once, to relay
    | it to the person.
    |
    */

    'default_reset_password' => env('ADMIN_DEFAULT_RESET_PASSWORD', 'CambiarClave123'),

];
