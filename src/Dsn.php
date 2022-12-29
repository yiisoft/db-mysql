<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * The Dsn class is typically used to parse a DSN string, which is a string that contains all the necessary information
 * to connect to a database SQL Server, such as the database driver, host, database name, port, options.
 *
 * It also allows you to access individual components of the DSN, such as the driver, host, database name or port.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.connection.php
 */
final class Dsn extends AbstractDsn
{
}
