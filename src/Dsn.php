<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Stringable;

/**
 * Represents a Data Source Name (DSN) for MySQL and MariaDB servers that's used to configure a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.connection.php
 */
final class Dsn implements Stringable
{
    /**
     * @param string $driver The database driver name.
     * @param string $host The database host name or IP address.
     * @param string $databaseName The database name to connect to.
     * @param string $port The database port. Empty string if not set.
     * @param string[] $options The database connection options. Default value to an empty array.
     *
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        public readonly string $driver = 'mysql',
        public readonly string $host = '127.0.0.1',
        public readonly string $databaseName = '',
        public readonly string $port = '3306',
        public readonly array $options = [],
    ) {}

    /**
     * @return string The Data Source Name, or DSN, has the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` property is used as the driver prefix of the DSN, all further property-value pairs
     * or key-value pairs of `options` property are rendered as `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']);
     * $driver = new Driver($dsn, 'username', 'password');
     * $connection = new Connection($driver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `mysql:host=127.0.0.1;dbname=yiitest;port=3306;charset=utf8mb4`.
     */
    public function __toString(): string
    {
        $dsn = "$this->driver:host=$this->host";

        if ($this->databaseName !== '') {
            $dsn .= ";dbname=$this->databaseName";
        }

        if ($this->port !== '') {
            $dsn .= ";port=$this->port";
        }

        foreach ($this->options as $key => $value) {
            $dsn .= ";$key=$value";
        }

        return $dsn;
    }
}
