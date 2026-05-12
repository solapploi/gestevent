<?php

declare(strict_types=1);

return [
    'host'       => $_ENV['MAIL_HOST']      ?? 'smtp.brevo.com',
    'port'       => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'user'       => $_ENV['MAIL_USER']      ?? '',
    'pass'       => $_ENV['MAIL_PASS']      ?? '',
    'from'       => $_ENV['MAIL_FROM']      ?? '',
    'from_name'  => $_ENV['MAIL_FROM_NAME'] ?? 'GestEvent',
    'encryption' => 'tls',
];
