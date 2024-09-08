<?php

declare(strict_types=1);

use Yiisoft\Db\Mysql\Column\ColumnFactory;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group mysql
 */
class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    public function testColumnFactory(): void
    {
        $db = $this->getConnection();
        $columnBuilderClass = $db->getColumnBuilderClass();

        $this->assertInstanceOf(ColumnFactory::class, $columnBuilderClass::columnFactory());
    }
}
