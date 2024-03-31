<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use ReflectionException;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaInterface;
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
     *
     * @throws ReflectionException
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
            'extra_default_value' => 'current_timestamp()',
            'privileges' => 'select,insert,update,references',
            'comment' => '',
        ]]);

        $this->assertInstanceOf(ColumnSchema::class, $column);
        $this->assertInstanceOf(Expression::class, $column->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP', $column->getDefaultValue());
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is provided
     * as NULL.
     *
     * @see https://github.com/yiisoft/yii2/issues/19047
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampAsNullInMariaDB(): void
    {
        $db = $this->getConnection();

        $schema = new Schema($db, DbHelper::getSchemaCache());

        $column = Assert::invokeMethod($schema, 'loadColumnSchema', [[
            'field' => 'emulated_MariaDB_field',
            'type' => 'timestamp',
            'collation' => null,
            'null' => 'NO',
            'key' => '',
            'default' => null,
            'extra' => '',
            'privileges' => 'select,insert,update,references',
            'comment' => '',
        ]]);

        $this->assertInstanceOf(ColumnSchema::class, $column);
        $this->assertEquals(null, $column->getDefaultValue());
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::columns
     *
     * @throws Exception
     */
    public function testColumnSchema(array $columns, string $tableName): void
    {
        $db = $this->getConnection();

        if (
            version_compare($db->getServerVersion(), '8.0.17', '>') &&
            !str_contains($db->getServerVersion(), 'MariaDB')
        ) {
            if ($tableName === 'type') {
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

            if ($tableName === 'animal') {
                $columns['id']['dbType'] = 'int';
                $columns['id']['size'] = null;
                $columns['id']['precision'] = null;
            }
        }

        parent::testColumnSchema($columns, $tableName);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::columnsTypeBit
     */
    public function testColumnSchemaWithTypeBit(array $columns): void
    {
        $this->columnSchema($columns, 'type_bit');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDefaultValueDatetimeColumn(): void
    {
        $tableName = '{{%datetime_test}}';
        $db = $this->getConnection();

        $oldMySQL = !(
            version_compare($db->getServerVersion(), '8.0.0', '>') &&
            !str_contains($db->getServerVersion(), 'MariaDB')
        );

        $columnsData = [
            'id' => ['int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', '', false],
            'd' => ['date DEFAULT \'2011-11-11\'', '2011-11-11', false],
            'dt' => ['datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'dt1' => ['datetime DEFAULT \'2011-11-11 00:00:00\'', '2011-11-11 00:00:00', false],
            'dt2' => ['datetime DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'ts' => ['timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'ts1' => ['timestamp DEFAULT \'2011-11-11 00:00:00\'', '2011-11-11 00:00:00', false],
            'ts2' => ['timestamp DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'simple_col' => ['varchar(40) DEFAULT \'uuid()\'', 'uuid()', false],
        ];
        if (!$oldMySQL) {
            $columnsData['ts4'] = ['date DEFAULT (CURRENT_DATE + INTERVAL 2 YEAR)', '(curdate() + interval 2 year)', true];
            $columnsData['uuid_col'] = ['varchar(40) DEFAULT (uuid())', 'uuid()', true];
        }

        $columns = [];
        foreach ($columnsData as $column => $columnData) {
            $columns[$column] = $columnData[0];
        }

        if ($db->getTableSchema($tableName, true) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, $columns, 'ENGINE=InnoDB DEFAULT CHARSET=utf8')->execute();

        $tableSchema = $db->getTableSchema($tableName);
        $this->assertNotNull($tableSchema);

        foreach ($tableSchema->getColumns() as $column) {
            $columnName = $column->getName();
            if ($columnsData[$columnName][2]) {
                $this->assertInstanceOf(Expression::class, $column->getDefaultValue());
            } else {
                $this->assertNotInstanceOf(Expression::class, $column->getDefaultValue());
            }
            $this->assertEquals($columnsData[$columnName][1], (string) $column->getDefaultValue());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
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

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame(['yiitest'], $schema->getSchemaNames());
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::columnsTypeChar
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

    /**
     * @throws NotSupportedException
     */
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

        foreach ($expectedTableNames as $tableName) {
            $this->assertContains($tableName, $tablesNames);
        }
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $viewExpected = match (str_contains($db->getServerVersion(), 'MariaDB')) {
            true => ['animal_view', 'user'],
            default => ['animal_view'],
        };

        $this->assertSame($viewExpected, $views);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider::tableSchemaWithDbSchemes
     */
    public function testTableSchemaWithDbSchemes(
        string $tableName,
        string $expectedTableName,
        string $expectedSchemaName = ''
    ): void {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock->method('queryAll')->willReturn([]);
        $mockDb = $this->createMock(PdoConnectionInterface::class);
        $mockDb->method('getQuoter')->willReturn($db->getQuoter());
        $mockDb
            ->method('createCommand')
            ->with(
                self::callback(static fn ($sql) => true),
                self::callback(
                    function ($params) use ($expectedTableName, $expectedSchemaName) {
                        $this->assertEquals($expectedTableName, $params[':tableName']);
                        $this->assertEquals($expectedSchemaName, $params[':schemaName']);

                        return true;
                    }
                )
            )
            ->willReturn($commandMock);

        $schema = new Schema($mockDb, DbHelper::getSchemaCache());
        $schema->getTablePrimaryKey($tableName);
    }

    public function testWorkWithCheckConstraint(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DDLQueryBuilder::addCheck is not supported by MySQL.'
        );

        parent::testWorkWithCheckConstraint();
    }

    public function testWorkWithDefaultValueConstraint(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DDLQueryBuilder::addDefaultValue is not supported by MySQL.'
        );

        parent::testWorkWithDefaultValueConstraint();
    }

    public function testWorkWithPrimaryKeyConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addPrimaryKey($tableName, $constraintName, $columnName)->execute();

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertInstanceOf(Constraint::class, $constraints);
        $this->assertEquals('', $constraints->getName());
        $this->assertEquals([$columnName], $constraints->getColumnNames());

        $db->createCommand()->dropPrimaryKey($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertNull($constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    public function withIndexDataProvider(): array
    {
        return array_merge(parent::withIndexDataProvider(), [
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_HASH,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_BTREE,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => SchemaInterface::INDEX_FULLTEXT,
                'indexMethod' => null,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => SchemaInterface::INDEX_SPATIAL,
                'indexMethod' => null,
                'columnType' => 'GEOMETRY NOT NULL',
            ],
        ]);
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\InvalidCallException
     */
    public function testTinyInt1(): void
    {
        $db = $this->getConnection(true);
        $tableName = '{{%tinyint}}';

        if ($db->getTableSchema($tableName)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            [
                'id' => new Column(SchemaInterface::TYPE_PK),
                'bool_col' => new Column(SchemaInterface::TYPE_BOOLEAN),
                'status' => new Column(SchemaInterface::TYPE_TINYINT, 1),
            ]
        )->execute();

        $status = 2;
        $insertedRow = $db->createCommand()->insertWithReturningPks($tableName, ['status' => $status, 'bool_col' => true]);
        $selectedRow = $db->createCommand('SELECT * FROM ' . $tableName . ' WHERE id=:id', ['id' => $insertedRow['id']])->queryOne();

        $this->assertEquals($status, $selectedRow['status']);
        $this->assertEquals(true, $selectedRow['bool_col']);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testNotConnectionPDO(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $schema = new Schema($db, DbHelper::getSchemaCache());

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Only PDO connections are supported.');

        $schema->refresh();
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     */
    public function testInsertDefaultValues(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $command->insert('negative_default_values', [])->execute();

        $row = (new Query($db))->from('negative_default_values')->one();

        $this->assertSame([
            'tinyint_col' => '-123',
            'smallint_col' => '-123',
            'int_col' => '-123',
            'bigint_col' => '-123',
            'float_col' => '-12345.6789',
            'numeric_col' => '-33.22',
        ], $row);
    }
}
