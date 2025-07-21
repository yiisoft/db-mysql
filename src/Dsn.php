<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Represents a Data Source Name (DSN) for MySQL and MariaDB servers that's used to configure a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
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
        string $databaseName = '',
        string $port = '3306',
        array $options = [],
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }
}
