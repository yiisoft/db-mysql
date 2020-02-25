<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\ColumnSchemaBuilder;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Tests\ColumnSchemaBuilderTest as AbstractColumnSchemaBuilderTest;

class ColumnSchemaBuilderTest extends AbstractColumnSchemaBuilderTest
{
    protected ?string $driverName = 'mysql';

    /**
     * @param string $type
     * @param int $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }

    /**
     * @return array
     */
    public function typesProvider(): array
    {
        return [
            ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['integer(10) COMMENT \'test\'', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
        ];
    }
}
