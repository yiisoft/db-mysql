<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Mysql\TableSchema;
use Yiisoft\Db\TestUtility\TestSchemaTrait;

use function array_map;
use function trim;
use function version_compare;

/**
 * @group mysql
 */
final class SchemaTest extends TestCase
{
    use TestSchemaTrait;

    public function getExpectedColumns(): array
    {
        $version = $this->getConnection()->getServerVersion();

        return [
            'int_col' => [
                'type' => 'integer',
                'dbType' => version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => version_compare($version, '8.0.17', '>') ? null : 11,
                'precision' => version_compare($version, '8.0.17', '>') ? null : 11,
                'scale' => null,
                'defaultValue' => null,
            ],
            'int_col2' => [
                'type' => 'integer',
                'dbType' => version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => version_compare($version, '8.0.17', '>') ? null : 11,
                'precision' => version_compare($version, '8.0.17', '>') ? null : 11,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'tinyint_col' => [
                'type' => 'tinyint',
                'dbType' => version_compare($version, '8.0.17', '>') ? 'tinyint' : 'tinyint(3)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => version_compare($version, '8.0.17', '>') ? null : 3,
                'precision' => version_compare($version, '8.0.17', '>') ? null : 3,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'smallint_col' => [
                'type' => 'smallint',
                'dbType' => version_compare($version, '8.0.17', '>') ? 'smallint' : 'smallint(1)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => version_compare($version, '8.0.17', '>') ? null : 1,
                'precision' => version_compare($version, '8.0.17', '>') ? null : 1,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'char_col' => [
                'type' => 'char',
                'dbType' => 'char(100)',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => 100,
                'scale' => null,
                'defaultValue' => null,
            ],
            'char_col2' => [
                'type' => 'string',
                'dbType' => 'varchar(100)',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => 100,
                'scale' => null,
                'defaultValue' => 'something',
            ],
            'char_col3' => [
                'type' => 'text',
                'dbType' => 'text',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'enum_col' => [
                'type' => 'string',
                'dbType' => "enum('a','B','c,D')",
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => ['a', 'B', 'c,D'],
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'float_col' => [
                'type' => 'double',
                'dbType' => 'double(4,3)',
                'phpType' => 'double',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 4,
                'precision' => 4,
                'scale' => 3,
                'defaultValue' => null,
            ],
            'float_col2' => [
                'type' => 'double',
                'dbType' => 'double',
                'phpType' => 'double',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => 1.23,
            ],
            'blob_col' => [
                'type' => 'binary',
                'dbType' => 'blob',
                'phpType' => 'resource',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'numeric_col' => [
                'type' => 'decimal',
                'dbType' => 'decimal(5,2)',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 5,
                'precision' => 5,
                'scale' => 2,
                'defaultValue' => '33.22',
            ],
            'time' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => '2002-01-01 00:00:00',
            ],
            'bool_col' => [
                'type' => 'boolean',
                'dbType' => 'tinyint(1)',
                'phpType' => 'boolean',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => 1,
                'scale' => null,
                'defaultValue' => null,
            ],
            'bool_col2' => [
                'type' => 'boolean',
                'dbType' => 'tinyint(1)',
                'phpType' => 'boolean',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => 1,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'ts_default' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
            ],
            'bit_col' => [
                'type' => 'integer',
                'dbType' => 'bit(8)',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 8,
                'precision' => 8,
                'scale' => null,
                'defaultValue' => 130, // b'10000010'
            ],
            'json_col' => [
                'type' => 'json',
                'dbType' => 'json',
                'phpType' => 'array',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
        ];
    }

    public function testLoadDefaultDatetimeColumn(): void
    {
        if (!version_compare($this->getConnection()->getServerVersion(), '5.6', '>=')) {
            $this->markTestSkipped('Default datetime columns are supported since MySQL 5.6.');
        }

        $sql = <<<SQL
CREATE TABLE  IF NOT EXISTS `datetime_test`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        $this->getConnection()->createCommand($sql)->execute();

        $schema = $this->getConnection()->getTableSchema('datetime_test');

        $dt = $schema->getColumn('dt');

        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP', (string) $dt->getDefaultValue());
    }

    public function testDefaultDatetimeColumnWithMicrosecs(): void
    {
        if (!version_compare($this->getConnection()->getServerVersion(), '5.6.4', '>=')) {
            $this->markTestSkipped(
                'CURRENT_TIMESTAMP with microseconds as default column value is supported since MySQL 5.6.4.'
            );
        }

        $sql = <<<SQL
CREATE TABLE  IF NOT EXISTS `current_timestamp_test`  (
  `dt` datetime(2) NOT NULL DEFAULT CURRENT_TIMESTAMP(2),
  `ts` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        $this->getConnection()->createCommand($sql)->execute();

        $schema = $this->getConnection()->getTableSchema('current_timestamp_test');

        $dt = $schema->getColumn('dt');

        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP(2)', (string) $dt->getDefaultValue());

        $ts = $schema->getColumn('ts');

        $this->assertInstanceOf(Expression::class, $ts->getDefaultValue());
        $this->assertEquals('CURRENT_TIMESTAMP(3)', (string) $ts->getDefaultValue());
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed as
     * CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
     *
     * {@see https://mariadb.com/kb/en/library/now/#description}
     * {@see https://github.com/yiisoft/yii2/issues/15167}
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampInMariaDB(): void
    {
        /**
         * We do not have a real database MariaDB >= 10.2.3 for tests, so we emulate the information that database
         * returns in response to the query `SHOW FULL COLUMNS FROM ...`
         */
        $schema = new Schema($this->getConnection(), $this->schemaCache);

        $column = $this->invokeMethod($schema, 'loadColumnSchema', [[
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
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $connection = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            $connection->getPDO()->setAttribute($name, $value);
        }

        $schema = $connection->getSchema();

        $tables = $schema->getTableNames();

        if ($connection->getDriverName() === 'sqlsrv') {
            $tables = array_map(static function ($item) {
                return trim($item, '[]');
            }, $tables);
        }

        $this->assertContains('customer', $tables);
        $this->assertContains('category', $tables);
        $this->assertContains('item', $tables);
        $this->assertContains('order', $tables);
        $this->assertContains('order_item', $tables);
        $this->assertContains('type', $tables);
        $this->assertContains('animal', $tables);
        $this->assertContains('animal_view', $tables);
    }

    public function constraintsProvider()
    {
        $result = $this->constraintsProviderTrait();

        $result['1: check'][2] = false;

        $result['2: primary key'][2]->name(null);

        $result['2: check'][2] = false;

        $result['3: check'][2] = false;

        $result['4: check'][2] = false;

        return $result;
    }

    /**
     * @dataProvider constraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraints(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $constraints = $this->getConnection()->getSchema()->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @depends testSchemaCache
     *
     * @dataProvider tableSchemaCachePrefixesProviderTrait
     *
     * @param string $tablePrefix
     * @param string $tableName
     * @param string $testTablePrefix
     * @param string $testTableName
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnection();
        $schema = $this->getConnection()->getSchema();

        $this->schemaCache->setEnable(true);

        $db->setTablePrefix($tablePrefix);

        $noCacheTable = $schema->getTableSchema($tableName, true);

        $this->assertInstanceOf(TableSchema::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);

        $testNoCacheTable = $schema->getTableSchema($testTableName);

        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);

        $schema->refreshTableSchema($tableName);

        $refreshedTable = $schema->getTableSchema($tableName, false);

        $this->assertInstanceOf(TableSchema::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);

        $schema->refreshTableSchema($testTablePrefix);

        $testRefreshedTable = $schema->getTableSchema($testTableName, false);

        $this->assertInstanceOf(TableSchema::class, $testRefreshedTable);
        $this->assertEquals($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);
    }

    public function testFindUniqueIndexes(): void
    {
        $db = $this->getConnection();

        try {
            $db->createCommand()->dropTable('uniqueIndex')->execute();
        } catch (Exception $e) {
        }

        $db->createCommand()->createTable('uniqueIndex', [
            'somecol' => 'string',
            'someCol2' => 'string',
        ])->execute();

        /* @var $schema Schema */
        $schema = $db->getSchema();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([], $uniqueIndexes);

        $db->createCommand()->createIndex('somecolUnique', 'uniqueIndex', 'somecol', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
        ], $uniqueIndexes);

        // create another column with upper case letter that fails postgres
        // see https://github.com/yiisoft/yii2/issues/10613
        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
            'someCol2Unique' => ['someCol2'],
        ], $uniqueIndexes);

        // see https://github.com/yiisoft/yii2/issues/13814
        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol2', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
            'someCol2Unique' => ['someCol2'],
            'another unique index' => ['someCol2'],
        ], $uniqueIndexes);
    }
}
