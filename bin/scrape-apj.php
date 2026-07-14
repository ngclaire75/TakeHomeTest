#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\ApjScraper;
use App\Config;
use App\Database;
use App\InaprocClient;
use App\ProductRepository;
use App\ProductSpecParser;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$config = new Config();

try {
    $database = new Database($config);
    $client = new InaprocClient($config);
    $repository = new ProductRepository($database->pdo());
    $specParser = new ProductSpecParser();

    $scraper = new ApjScraper($client, $repository, $specParser, $config);
    $collected = $scraper->run();
} catch (\Throwable $e) {
    fwrite(STDERR, 'Scraper failed: ' . $e->getMessage() . "\n");
    exit(1);
}

exit($collected >= $config->minRecords ? 0 : 2);
