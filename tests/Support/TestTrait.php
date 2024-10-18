<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Connection(new Driver($this->getDsn(), 'root', ''), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        $dsn = (new Dsn(databaseName: 'yiitest', options: ['charset' => 'utf8mb4']))->asString();

        return new Connection(new Driver($dsn, 'root', ''), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn(databaseName: 'yiitest', options: ['charset' => 'utf8mb4']))->asString();
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
}
