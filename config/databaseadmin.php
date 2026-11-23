<?php

return [
    /*
    | "Reset all data" on the master-admin Database page.
    | Allowed everywhere except production, where it must be switched on deliberately with DB_ADMIN_ALLOW_RESET=true
    | (and switched off again afterwards).
    */
    'allow_reset' => (bool) env('DB_ADMIN_ALLOW_RESET', env('APP_ENV', 'production') !== 'production'),

    /** The exact phrase the admin must type to confirm. */
    'reset_phrase' => 'RESET ALL DATA',

    /** Accounts with these roles are never reset: not their account, their sign-in sessions, nor the data they own. */
    'protected_roles' => ['DEF-ADMIN', 'DEF-MASTERADMIN'],
];
