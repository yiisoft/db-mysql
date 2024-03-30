<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\SqlParser;
use Yiisoft\Db\Tests\AbstractSqlParserTest;

/**
 * @group mysql
 */
final class SqlParserTest extends AbstractSqlParserTest
{
    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }

    /** @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SqlParserProvider::getNextPlaceholder */
    public function testGetNextPlaceholder(string $sql, string|null $expectedPlaceholder, int|null $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }
}
