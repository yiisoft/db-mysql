<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;

use function version_compare;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed as
     * CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
     *
     * {@link https://mariadb.com/kb/en/library/now/#description}
     * {@link https://github.com/yiisoft/yii2/issues/15167}
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampInMariaDB(): void
    {
        /**
         * We do not have a real database MariaDB >= 10.2.3 for tests, so we emulate the information that database
         * returns in response to the query `SHOW FULL COLUMNS FROM ...`
         */
        $db = $this->getConnection();

        $schema = new Schema($db, DbHelper::getSchemaCache());

        $column = Assert::invokeMethod($schema, 'loadColumnSchema', [[
            'field' => 'emulated_MariaDB_field',
            'type' => 'timestamp',
            'collation' => null,
            'null' => 'NO',
            'key' => '',
            'default' => 'current_timestamp()',
            'extra' => '',
            'privileges' => 'select,insert,update,references',
            'comment' => '',
        ]]);

        $this->assertInstanceOf(ColumnSchema::class, $column);
        $this->assertInstanceOf(Expression::class, $column->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP', $column->getDefaultValue());
    }

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

    public function testDefaultValueDatetimeColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `datetime_test`  (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $command->setSql($sql)->execute();

        $schema = $schema->getTableSchema('datetime_test');

        $this->assertNotNull($schema);

        $dt = $schema->getColumn('dt');

        $this->assertNotNull($dt);

        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP', (string) $dt->getDefaultValue());
    }

    public function testDefaultValueDatetimeColumnWithMicrosecs(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `current_timestamp_test`  (
            `dt` datetime(2) NOT NULL DEFAULT CURRENT_TIMESTAMP(2),
            `ts` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $command->setSql($sql)->execute();

        $schema = $schema->getTableSchema('current_timestamp_test');

        $this->assertNotNull($schema);

        $dt = $schema->getColumn('dt');

        $this->assertNotNull($dt);

        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP(2)', (string) $dt->getDefaultValue());

        $ts = $schema->getColumn('ts');

        $this->assertNotNull($ts);
        $this->assertInstanceOf(Expression::class, $ts->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP(3)', (string) $ts->getDefaultValue());
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

    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame(['yiitest'], $schema->getSchemaNames());
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::columnsTypeChar()
     */
    public function testGetStringFieldsSize(
        string $columnName,
        string $columnType,
        int|null $columnSize,
        string $columnDbType
    ): void {
        parent::testGetStringFieldsSize($columnName, $columnType, $columnSize, $columnDbType);
    }

    public function testGetTableChecks(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\Schema::loadTableChecks is not supported by MySQL.'
        );

        parent::testGetTableChecks();
    }

    public function testGetTableNamesWithSchema(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tablesNames = $schema->getTableNames('yiitest');

        $expectedTableNames = [
            'alpha',
            'animal',
            'animal_view',
            'beta',
            'bit_values',
            'category',
            'comment',
            'composite_fk',
            'constraints',
            'customer',
            'default_pk',
            'department',
            'document',
            'dossier',
            'employee',
            'item',
            'negative_default_values',
            'null_values',
            'order',
            'order_item',
            'order_item_with_null_fk',
            'order_with_null_fk',
            'profile',
            'quoter',
            'storage',
            'T_constraints_1',
            'T_constraints_2',
            'T_constraints_3',
            'T_constraints_4',
            'T_upsert',
            'T_upsert_1',
            'type',
        ];

        if (version_compare($db->getServerVersion(), '8.0', '<')) {
            $expectedTableNames = [
                'T_constraints_1',
                'T_constraints_2',
                'T_constraints_3',
                'T_constraints_4',
                'T_upsert',
                'T_upsert_1',
                'alpha',
                'animal',
                'animal_view',
                'beta',
                'bit_values',
                'category',
                'comment',
                'composite_fk',
                'constraints',
                'customer',
                'default_pk',
                'department',
                'document',
                'dossier',
                'employee',
                'item',
                'negative_default_values',
                'null_values',
                'order',
                'order_item',
                'order_item_with_null_fk',
                'order_with_null_fk',
                'profile',
                'quoter',
                'storage',
                'type',
            ];
        }

        $this->assertSame($expectedTableNames, $tablesNames);
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
