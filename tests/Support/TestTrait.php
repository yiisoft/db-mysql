<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Mysql\PdoConnection;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Mysql\PdoDriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $db = new PdoConnection(new PdoDriver($this->getDsn(), 'root', ''), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');
        }

        return $db;
    }

    protected static function getDb(): ConnectionPDOInterface
    {
        $dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();

        return new PdoConnection(new PdoDriver($dsn, 'root', ''), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306', ['charset' => 'utf8mb4']))->asString();
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
}
