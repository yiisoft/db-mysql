<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Column\ColumnDefinitionParser;
use Yiisoft\Db\Tests\Common\CommonColumnDefinitionParserTest;

/**
 * @group mysql
 */
final class ColumnDefinitionParserTest extends CommonColumnDefinitionParserTest
{
    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }
}
