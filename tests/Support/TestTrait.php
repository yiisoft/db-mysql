<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    protected function getConnection(bool $fixture = false): Connection
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');
        }

        return $db;
    }

    protected static function getDb(): Connection
    {
        $dsn = (new Dsn(
            host: self::getHost(),
            databaseName: self::getDatabaseName(),
            port: self::getPort(),
            options: ['charset' => 'utf8mb4'],
        ))->asString();

        return new Connection(new Driver($dsn, self::getUsername(), self::getPassword()), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn(
                host: self::getHost(),
                databaseName: self::getDatabaseName(),
                port: self::getPort(),
                options: ['charset' => 'utf8mb4'],
            ))->asString();
        }

        return $this->dsn;
    }

    protected function getDriverName(): string
    {
        return 'mysql';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');

        $db->close();
    }

    private function getDriver(): Driver
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getDatabaseName(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_DATABASE') ?: 'yiitest';
        }

        return getenv('YII_MYSQL_DATABASE') ?: 'yiitest';
    }

    private static function getHost(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_HOST') ?: '127.0.0.1';
        }

        return getenv('YII_MYSQL_HOST') ?: '127.0.0.1';
    }

    private static function getPort(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_PORT') ?: '3306';
        }

        return getenv('YII_MYSQL_PORT') ?: '3306';
    }

    private static function getUsername(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_USER') ?: 'root';
        }

        return getenv('YII_MYSQL_USER') ?: 'root';
    }

    private static function getPassword(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_PASSWORD') ?: '';
        }

        return getenv('YII_MYSQL_PASSWORD') ?: '';
    }

    private static function isMariadb(): bool
    {
        return getenv('YII_MYSQL_TYPE') === 'mariadb';
    }
}
