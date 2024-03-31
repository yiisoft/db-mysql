<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use Yiisoft\Db\Driver\Pdo\AbstractPdoDriver;

/**
 * Implements the MySQL, MariaDB driver based on the PDO (PHP Data Objects) extension.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.php
 */
final class Driver extends AbstractPdoDriver
{
    /**
     * @return PDO
     */
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        if (PHP_VERSION_ID >= 80100) {
            $this->attributes += [PDO::ATTR_STRINGIFY_FETCHES => true];
        }

        $pdo = parent::createConnection();

        if ($this->charset !== null) {
            $pdo->exec('SET NAMES ' . $pdo->quote($this->charset));
        }

        if ($this->charset === null && !str_contains($this->dsn, 'charset')) {
            $pdo->exec('SET NAMES ' . $pdo->quote('utf8mb4'));
        }

        return $pdo;
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }
}
