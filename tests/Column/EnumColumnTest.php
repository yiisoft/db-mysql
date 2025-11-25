<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Column;

use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Tests\Common\CommonEnumColumnTest;

final class EnumColumnTest extends CommonEnumColumnTest
{
    use IntegrationTestTrait;

    #[TestWith(['INT CHECK (status IN (1, 2, 3))'])]
    #[TestWith(["TEXT CHECK (status <> 'abc')"])]
    public function testNonEnumCheck(string $columnDefinition): void
    {
        $this->dropTable('test_enum_table');
        $this->executeStatements(
            <<<SQL
            CREATE TABLE test_enum_table (
                id INT,
                status $columnDefinition
            )
            SQL,
        );

        $db = $this->getSharedConnection();
        $column = $db->getTableSchema('test_enum_table')->getColumn('status');

        $this->assertNotInstanceOf(EnumColumn::class, $column);

        $this->dropTable('test_enum_table');
    }

    protected function createDatabaseObjectsStatements(): array
    {
        return [
            <<<SQL
            CREATE TABLE tbl_enum (
                id INT,
                status ENUM('active', 'unactive', 'pending')
            );
            SQL,
        ];
    }

    protected function dropDatabaseObjectsStatements(): array
    {
        return [
            'DROP TABLE IF EXISTS tbl_enum',
        ];
    }
}
