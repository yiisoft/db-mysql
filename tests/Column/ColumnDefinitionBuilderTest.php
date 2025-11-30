<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\Mysql\Tests\Support\TestConnection;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;

final class ColumnDefinitionBuilderTest extends TestCase
{
    public static function dataBuild(): iterable
    {
        yield 'integer' => ['int', new IntegerColumn()];
        yield 'enum' => ["enum('a','b','c')", new EnumColumn(values: ['a', 'b', 'c'])];
        yield 'enum-upper-case' => ["ENUM('a','b','c')", new EnumColumn(dbType: 'ENUM', values: ['a', 'b', 'c'])];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(string $expected, ColumnInterface $column): void
    {
        $builder = $this->createColumnDefinitionBuilder();

        $result = $builder->build($column);

        $this->assertSame($expected, $result);
    }

    private function createColumnDefinitionBuilder(): ColumnDefinitionBuilder
    {
        return new ColumnDefinitionBuilder(
            TestConnection::getShared()->getQueryBuilder(),
        );
    }
}
