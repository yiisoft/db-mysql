<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Stringable;

/**
 * Represents a Data Source Name (DSN) with unix socket for MySQL and MariaDB servers that's used to configure
 * a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.connection.php
 */
final class DsnSocket implements Stringable
{
    /**
     * @param string $driver The database driver to use.
     * @param string $unixSocket The unix socket to connect to.
     * @param string $databaseName The database name to connect to.
     * @param string[] $options The options to use.
     */
    public function __construct(
        public readonly string $driver = 'mysql',
        public readonly string $unixSocket = '/var/run/mysqld/mysqld.sock',
        public readonly string $databaseName = '',
        public readonly array $options = [],
    ) {
    }

    /**
     * @return string The Data Source Name, or DSN, has the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` property is used as the driver prefix of the DSN, all further property-value pairs
     * or key-value pairs of `options` property are rendered as `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8mb4']);
     * $pdoDriver = new PDODriver($dsn, 'username', 'password');
     * $connection = new Connection($pdoDriver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest;charset=utf8mb4`.
     */
    public function __toString(): string
    {
        $dsn = "$this->driver:unix_socket=$this->unixSocket";

        if ($this->databaseName !== '') {
            $dsn .= ";dbname=$this->databaseName";
        }

        foreach ($this->options as $key => $value) {
            $dsn .= ";$key=$value";
        }

        return $dsn;
    }
}
