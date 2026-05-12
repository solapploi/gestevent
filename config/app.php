<?php

declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME'] ?? 'GestEvent',
    'url'      => $_ENV['APP_URL']  ?? 'http://localhost',
    'env'      => $_ENV['APP_ENV']  ?? 'production',
    'timezone' => 'Europe/Paris',
    'locale'   => 'fr_FR',
];
