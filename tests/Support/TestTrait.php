<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
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

    private function getDriver(): PdoDriverInterface
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getDatabaseName(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_DATABASE') ?: '';
        }

        return getenv('YII_MYSQL_DATABASE') ?: '';
    }

    private static function getHost(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_HOST') ?: '';
        }

        return getenv('YII_MYSQL_HOST') ?: '';
    }

    private static function getPort(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_PORT') ?: '';
        }

        return getenv('YII_MYSQL_PORT') ?: '';
    }

    private static function getUsername(): string
    {
        if (self::isMariadb()) {
            return getenv('YII_MARIADB_USER') ?: '';
        }

        return getenv('YII_MYSQL_USER') ?: '';
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
