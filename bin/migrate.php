#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\Database;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$config = new Config();
$database = new Database($config);
$database->migrate(__DIR__ . '/../database/migrations');

fwrite(STDOUT, "Migrations complete.\n");
