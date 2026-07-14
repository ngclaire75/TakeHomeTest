<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(Config $config)
    {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config->dbHost, $config->dbPort, $config->dbName);

        $this->pdo = new PDO($dsn, $config->dbUser, $config->dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function migrate(string $migrationsDir): void
    {
        $files = glob(rtrim($migrationsDir, '/') . '/*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            $this->pdo->exec($sql);
            fwrite(STDOUT, 'Applied migration: ' . basename($file) . PHP_EOL);
        }
    }
}
