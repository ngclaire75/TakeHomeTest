<?php

declare(strict_types=1);

namespace App;

final class Config
{
    public readonly string $dbHost;
    public readonly string $dbPort;
    public readonly string $dbName;
    public readonly string $dbUser;
    public readonly string $dbPassword;
    public readonly string $baseUri;
    public readonly string $categoryId;
    public readonly int $minRecords;
    public readonly int $perPage;
    public readonly int $delayMs;

    public function __construct()
    {
        $this->dbHost = $_ENV['PGHOST'] ?? 'localhost';
        $this->dbPort = $_ENV['PGPORT'] ?? '5432';
        $this->dbName = $_ENV['PGDATABASE'] ?? 'inaproc_apj';
        $this->dbUser = $_ENV['PGUSER'] ?? 'postgres';
        $this->dbPassword = $_ENV['PGPASSWORD'] ?? 'postgres';

        $this->baseUri = rtrim($_ENV['INAPROC_BASE_URI'] ?? 'https://katalog.inaproc.id', '/');
        $this->categoryId = $_ENV['SCRAPER_CATEGORY_ID'] ?? 'aa2c3a44-36fa-49a6-b9f6-3188b7533d4f';
        $this->minRecords = (int) ($_ENV['SCRAPER_MIN_RECORDS'] ?? 120);
        $this->perPage = (int) ($_ENV['SCRAPER_PER_PAGE'] ?? 60);
        $this->delayMs = (int) ($_ENV['SCRAPER_DELAY_MS'] ?? 400);
    }
}
