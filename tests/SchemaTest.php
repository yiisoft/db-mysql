<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;

use function version_compare;

/**
 * @group mysql
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::columns()
     */
    public function testColumnSchema(array $columns): void
    {
        $db = $this->getConnection();


        if (version_compare($db->getServerVersion(), '8.0.17', '>')) {
            // int_col Mysql 8.0.17+.
            $columns['int_col']['dbType'] = 'int';
            $columns['int_col']['size'] = null;
            $columns['int_col']['precision'] = null;

            // int_col2 Mysql 8.0.17+.
            $columns['int_col2']['dbType'] = 'int';
            $columns['int_col2']['size'] = null;
            $columns['int_col2']['precision'] = null;

            // tinyint_col Mysql 8.0.17+.
            $columns['tinyint_col']['dbType'] = 'tinyint';
            $columns['tinyint_col']['size'] = null;
            $columns['tinyint_col']['precision'] = null;

            // smallint_col Mysql 8.0.17+.
            $columns['smallint_col']['dbType'] = 'smallint';
            $columns['smallint_col']['size'] = null;
            $columns['smallint_col']['precision'] = null;
        }

        parent::testColumnSchema($columns);
    }

    public function testGetSchemaChecks(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\Schema::loadTableChecks is not supported by MySQL.'
        );

        parent::testGetSchemaChecks();
    }

    public function testGetSchemaDefaultValues(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\Schema::loadTableDefaultValues is not supported by MySQL.'
        );

        parent::testGetSchemaDefaultValues();
    }

    public function testGetTableChecks(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\Schema::loadTableChecks is not supported by MySQL.'
        );

        parent::testGetTableChecks();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints()
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints()
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints()
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }
}
