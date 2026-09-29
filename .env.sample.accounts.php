<?php
declare(strict_types=1);

/*
 * Define private accounts here instead of committing them to accounts.php.
 * Copy this file to .env.accounts.php to activate these accounts; that file is
 * gitignored. A database-backed account system may replace this file in the
 * future.
 */

return [
    [
        'display_name' => 'Placeholder User',
        'username' => 'placeholder-user',
        'password_hash' => $hash,
        'role' => 'viewer',
        'patient_ids' => ['sample-patient'],
    ],
];
