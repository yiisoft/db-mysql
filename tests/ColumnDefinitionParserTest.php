<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Mysql\Column\ColumnDefinitionParser;
use Yiisoft\Db\Mysql\Tests\Provider\ColumnDefinitionParserProvider;
use Yiisoft\Db\Syntax\ColumnDefinitionParserInterface;
use Yiisoft\Db\Tests\Common\CommonColumnDefinitionParserTest;

/**
 * @group mysql
 */
final class ColumnDefinitionParserTest extends CommonColumnDefinitionParserTest
{
    #[DataProviderExternal(ColumnDefinitionParserProvider::class, 'parse')]
    public function testParse(string $definition, array $expected): void
    {
        parent::testParse($definition, $expected);
    }

    protected function createColumnDefinitionParser(): ColumnDefinitionParserInterface
    {
        return new ColumnDefinitionParser();
    }
}
