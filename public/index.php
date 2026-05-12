<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

use App\Core\App;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

$app = new App();
$app->run();
