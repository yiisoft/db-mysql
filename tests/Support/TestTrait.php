<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Mysql\ConnectionPDO;
use Yiisoft\Db\Mysql\PDODriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = 'mysql:host=127.0.0.1;dbname=yiitest;port=3306';

    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $db = new ConnectionPDO(
            new PDODriver($this->dsn, 'root', ''),
            DbHelper::getQueryCache(),
            DbHelper::getSchemaCache(),
        );

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/mysql.sql');
        }

        return $db;
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
