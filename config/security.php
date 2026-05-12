<?php

declare(strict_types=1);

return [
    'qr_secret_key'  => $_ENV['QR_SECRET_KEY']  ?? '',
    'session_secret' => $_ENV['SESSION_SECRET']  ?? '',
    'token_ttl'      => 86400 * 30,
    'rate_limit'     => [
        'max_attempts' => 5,
        'window'       => 600,
    ],
    'bcrypt_cost'    => 12,
];
