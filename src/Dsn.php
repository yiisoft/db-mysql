<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Implement a Data Source Name (DSN) for an MySQL, MariaDB.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.connection.php
 */
final class Dsn extends AbstractDsn
{
    /**
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        string $driver = 'mysql',
        string $host = '127.0.0.1',
        string|null $databaseName = null,
        string $port = '3306',
        array $options = []
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }
}
