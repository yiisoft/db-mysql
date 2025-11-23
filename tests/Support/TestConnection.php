<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Tests\Support\TestHelper;

final class TestConnection
{
    private static ?string $dsn = null;
    private static ?Connection $connection = null;

    public static function getShared(): Connection
    {
        $db = self::$connection ??= self::create();
        $db->getSchema()->refresh();
        return $db;
    }

    public static function getServerVersion(): string
    {
        return self::getShared()->getServerInfo()->getVersion();
    }

    public static function dsn(): string
    {
        return self::$dsn ??= (string) new Dsn(
            host: self::host(),
            databaseName: self::databaseName(),
            port: self::port(),
            options: ['charset' => 'utf8mb4'],
        );
    }

    public static function create(?string $dsn = null): Connection
    {
        return new Connection(self::createDriver($dsn), TestHelper::createMemorySchemaCache());
    }

    public static function createDriver(?string $dsn = null): Driver
    {
        $driver = new Driver($dsn ?? self::dsn(), self::username(), self::password());
        $driver->charset('utf8mb4');
        return $driver;
    }

    public static function databaseName(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_DATABASE') ?: 'yiitest';
        }

        return getenv('YII_MYSQL_DATABASE') ?: 'yiitest';
    }

    public static function isMariadb(): bool
    {
        return getenv('YII_MYSQL_TYPE') === 'mariadb';
    }

    private static function host(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_HOST') ?: '127.0.0.1';
        }

        return getenv('YII_MYSQL_HOST') ?: '127.0.0.1';
    }

    private static function port(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_PORT') ?: '3306';
        }

        return getenv('YII_MYSQL_PORT') ?: '3306';
    }

    private static function username(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_USER') ?: 'root';
        }

        return getenv('YII_MYSQL_USER') ?: 'root';
    }

    private static function password(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_PASSWORD') ?: '';
        }

        return getenv('YII_MYSQL_PASSWORD') ?: '';
    }
}
