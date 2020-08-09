<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Schema\ColumnSchema;
use Yiisoft\Db\Mysql\Schema\Schema;
use Yiisoft\Db\Tests\SchemaTest as AbstractSchemaTest;

class SchemaTest extends AbstractSchemaTest
{
    protected ?string $driverName = 'mysql';

    public function testLoadDefaultDatetimeColumn(): void
    {
        if (!version_compare($this->getConnection()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION), '5.6', '>=')) {
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
        if (!version_compare($this->getConnection()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION), '5.6.4', '>=')) {
            $this->markTestSkipped('CURRENT_TIMESTAMP with microseconds as default column value is supported since MySQL 5.6.4.');
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

    public function testGetSchemaNames()
    {
        $this->markTestSkipped('Schemas are not supported in MySQL.');
    }

    public function constraintsProvider()
    {
        $result = parent::constraintsProvider();

        $result['1: check'][2] = false;

        $result['2: primary key'][2]->name(null);
        $result['2: check'][2] = false;

        // Work aroung bug in MySQL 5.1 - it creates only this table in lowercase. O_o
        //$result['3: foreign key'][2][0]->setForeignTableName(new AnyCaseValue('T_constraints_2'));

        $result['3: check'][2] = false;

        $result['4: check'][2] = false;

        return $result;
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
        $schema = new Schema($this->getConnection());

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

    public function getExpectedColumns()
    {
        $version = $this->getConnection()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION);

        return array_merge(
            parent::getExpectedColumns(),
            [
                'int_col' => [
                    'type' => 'integer',
                    'dbType' => \version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
                    'phpType' => 'integer',
                    'allowNull' => false,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => \version_compare($version, '8.0.17', '>') ? null : 11,
                    'precision' => \version_compare($version, '8.0.17', '>') ? null : 11,
                    'scale' => null,
                    'defaultValue' => null,
                ],
                'int_col2' => [
                    'type' => 'integer',
                    'dbType' => \version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => \version_compare($version, '8.0.17', '>') ? null : 11,
                    'precision' => \version_compare($version, '8.0.17', '>') ? null : 11,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'tinyint_col' => [
                    'type' => 'tinyint',
                    'dbType' => \version_compare($version, '8.0.17', '>') ? 'tinyint' : 'tinyint(3)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => \version_compare($version, '8.0.17', '>') ? null : 3,
                    'precision' => \version_compare($version, '8.0.17', '>') ? null : 3,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'smallint_col' => [
                    'type' => 'smallint',
                    'dbType' =>  \version_compare($version, '8.0.17', '>') ? 'smallint' : 'smallint(1)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => \version_compare($version, '8.0.17', '>') ? null : 1,
                    'precision' => \version_compare($version, '8.0.17', '>') ? null : 1,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
            ]
        );
    }
}
