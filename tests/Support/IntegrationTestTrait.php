<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Support;

use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Tests\Support\TestHelper;

trait IntegrationTestTrait
{
    protected function createConnection(): Connection
    {
        return new Connection(
            TestConnection::createDriver(),
            TestHelper::createMemorySchemaCache(),
        );
    }

    protected function getDefaultFixture(): string
    {
        return __DIR__ . '/Fixture/mysql.sql';
    }

    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], '`', $sql);
    }
}
