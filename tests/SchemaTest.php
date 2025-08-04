<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Mysql\Tests\Provider\SchemaProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
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

    #[DataProviderExternal(SchemaProvider::class, 'columns')]
    public function testColumns(array $columns, string $tableName): void
    {
        $db = $this->getConnection();
        $serverVersion = $db->getServerInfo()->getVersion();
        $db->close();

        if (
            version_compare($serverVersion, '8.0.17', '>') &&
            !str_contains($serverVersion, 'MariaDB')
        ) {
            if ($tableName === 'type') {
                $columns['int_col']->size(null);
                $columns['int_col2']->size(null);
                $columns['bigunsigned_col']->size(null);
                $columns['tinyint_col']->size(null);
                $columns['smallint_col']->size(null);
                $columns['mediumint_col']->size(null);
            }

            if ($tableName === 'animal') {
                $columns['id']->size(null);
            }

            if ($tableName === 'T_constraints_1') {
                $columns['C_id']->size(null);
                $columns['C_not_null']->size(null);
                $columns['C_unique']->size(null);
                $columns['C_default']->size(null);
            }
        }

        parent::testColumns($columns, $tableName);
    }

    #[DataProviderExternal(SchemaProvider::class, 'columnsTypeBit')]
    public function testColumnWithTypeBit(array $columns): void
    {
        $this->assertTableColumns($columns, 'type_bit');
    }

    public function testDefaultValueDatetimeColumn(): void
    {
        $tableName = '{{%datetime_test}}';
        $db = $this->getConnection();
        $serverVersion = $db->getServerInfo()->getVersion();

        $oldMySQL = !(
            version_compare($serverVersion, '8.0.0', '>') &&
            !str_contains($serverVersion, 'MariaDB')
        );

        $utcTimezone = new DateTimeZone('UTC');
        $dbTimezone = new DateTimeZone($db->getServerInfo()->getTimezone());

        $columnsData = [
            'id' => ['int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', ''],
            'd' => ['date DEFAULT \'2011-11-11\'', new DateTimeImmutable('2011-11-11', $utcTimezone)],
            'dt' => ['datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            'dt1' => ['datetime DEFAULT \'2011-11-11 00:00:00\'', new DateTimeImmutable('2011-11-11 00:00:00', $utcTimezone)],
            'dt2' => ['datetime DEFAULT CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            'ts' => ['timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            'ts1' => ['timestamp DEFAULT \'2011-11-11 00:00:00\'', new DateTimeImmutable('2011-11-11 00:00:00', $dbTimezone)],
            'ts2' => ['timestamp DEFAULT CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            'simple_col' => ['varchar(40) DEFAULT \'uuid()\'', 'uuid()'],
        ];
        if (!$oldMySQL) {
            $columnsData['ts4'] = ['date DEFAULT (CURRENT_DATE + INTERVAL 2 YEAR)', new Expression('(curdate() + interval 2 year)')];
            $columnsData['uuid_col'] = ['varchar(40) DEFAULT (uuid())', new Expression('(uuid())')];
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
            $this->assertEquals($columnsData[$columnName][1], $column->getDefaultValue());
        }

        $db->close();
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

        $db->close();
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

        $this->assertSame([self::getDatabaseName()], $schema->getSchemaNames());

        $db->close();
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
        $tablesNames = $schema->getTableNames(self::getDatabaseName());

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

        $db->close();
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $viewExpected = match (str_contains($db->getServerInfo()->getVersion(), 'MariaDB')) {
            true => ['animal_view', 'user'],
            default => ['animal_view'],
        };

        $this->assertSame($viewExpected, $views);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaWithDbSchemes')]
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
        $schema->getTablePrimaryKey($tableName, true);

        $db->close();
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

        $this->assertEquals(
            new Index('PRIMARY', [$columnName], true, true),
            $db->getSchema()->getTablePrimaryKey($tableName),
        );

        $db->createCommand()->dropPrimaryKey($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertNull($constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    public function testTinyInt1()
    {
        $db = $this->getConnection(true);
        $tableName = '{{%tinyint}}';

        if ($db->getTableSchema($tableName)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            [
                'id' => ColumnBuilder::primaryKey(),
                'bool_col' => ColumnBuilder::boolean(),
                'status' => ColumnBuilder::tinyint(),
            ]
        )->execute();

        $status = 2;
        $insertedRow = $db->createCommand()->insertReturningPks($tableName, ['status' => $status, 'bool_col' => true]);
        $selectedRow = $db->createCommand('SELECT * FROM ' . $tableName . ' WHERE id=:id', ['id' => $insertedRow['id']])->queryOne();

        $this->assertEquals($status, $selectedRow['status']);
        $this->assertEquals(true, $selectedRow['bool_col']);

        $db->close();
    }

    public function testNotConnectionPDO(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $schema = new Schema($db, DbHelper::getSchemaCache());

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Only PDO connections are supported.');

        $schema->refresh();
    }

    public function testInsertDefaultValues()
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

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'resultColumns')]
    public function testGetResultColumn(ColumnInterface|null $expected, array $metadata): void
    {
        parent::testGetResultColumn($expected, $metadata);
    }
}
