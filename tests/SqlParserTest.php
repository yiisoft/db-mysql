<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Mysql\SqlParser;
use Yiisoft\Db\Mysql\Tests\Provider\SqlParserProvider;
use Yiisoft\Db\Tests\Common\CommonSqlParserTest;

/**
 * @group mysql
 */
final class SqlParserTest extends CommonSqlParserTest
{
    #[DataProviderExternal(SqlParserProvider::class, 'getNextPlaceholder')]
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
