<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Exception\Exception;
use yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestSupport\TestCommandTrait;

/**
 * @group mysql
 */
final class CommandTest extends TestCase
{
    use TestCommandTrait;

    protected string $upsertTestCharCast = 'CONVERT([[address]], CHAR)';

    public function testAddCheck(): void
    {
        $db = $this->getConnection();
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\PDO\QueryBuilderPDOMysql::addCheck is not supported by MySQL.');
        $db->createCommand()->addCheck('noExist', 'noExist', 'noExist')->execute();
    }

    public function testAddDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_pk';
        $name = 'test_pk_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null',
            'int2' => 'integer not null',
        ])->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1'])->execute();

        $this->assertEquals(['int1'], $schema->getTablePrimaryKey($tableName, true)->getColumnNames());

        $db->createCommand()->dropPrimaryKey($name, $tableName)->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertEquals(['int1', 'int2'], $schema->getTablePrimaryKey($tableName, true)->getColumnNames());
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider batchInsertSqlProviderTrait
     *
     * @param string $table
     * @param array $columns
     * @param array $values
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = []
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert($table, $columns, $values);

        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider bindParamsNonWhereProviderTrait
     *
     * @param string $sql
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection();

        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        )->execute();

        $params = [
            ':email' => 'testParams@example.com',
            ':len' => 5,
        ];

        $command = $db->createCommand($sql, $params);

        $this->assertEquals('Params', $command->queryScalar());
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider getRawSqlProviderTrait
     *
     * @param string $sql
     * @param array $params
     * @param string $expectedRawSql
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand($sql, $params);

        $this->assertEquals($expectedRawSql, $command->getRawSql());
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider invalidSelectColumnsProviderTrait
     *
     * @param mixed $invalidSelectColumns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testInsertSelectFailed($invalidSelectColumns): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->select($invalidSelectColumns)->from('{{customer}}');

        $command = $db->createCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected select query object with enumerated (named) parameters');

        $command->insert(
            '{{customer}}',
            $query
        )->execute();
    }

    /**
     * Test command upsert.
     *
     * @dataProvider upsertProviderTrait
     *
     * @param array $firstData
     * @param array $secondData
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $firstData);

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $secondData);
    }
}
