<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group mysql
 */
class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    public function getColumnBuilderClass(): string
    {
        return ColumnBuilder::class;
    }

    #[DataProviderExternal(ColumnBuilderProvider::class, 'buildingMethods')]
    public function testBuildingMethods(
        string $buildingMethod,
        array $args,
        string $expectedInstanceOf,
        string $expectedType,
        array $expectedMethodResults = [],
    ): void {
        parent::testBuildingMethods($buildingMethod, $args, $expectedInstanceOf, $expectedType, $expectedMethodResults);
    }
}
