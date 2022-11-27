<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

/**
 * @group mysql
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CONVERT([[address]], CHAR)';

    public function testAddCheck(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableChecks() is not supported by Mysql.');

        parent::testAddCheck();
    }

    public function testAddDefaultValue(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableDefaultValues() is not supported by MySQL.');

        parent::testAddDefaultValue();
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->alterColumn('{{customer}}', 'email', 'text')->execute();
        $schema = $db->getSchema();
        $columns = $schema->getTableSchema('{{customer}}')->getColumns();

        $this->assertArrayHasKey('email', $columns);
        $this->assertSame('text', $columns['email']->getDbType());
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::createIndex()
     *
     * @throws Throwable
     */
    public function testCreateIndex(
        string $name,
        string $tableName,
        array|string $column,
        string|null $indexType,
        string|null $indexMethod,
    ): void {
        parent::testCreateIndex($name, $tableName, $column, $indexType, $indexMethod);
    }

    public function testDropCheck(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableChecks() is not supported by Mysql.');

        parent::testDropCheck();
    }

    public function testDropDefaultValue(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableDefaultValues() is not supported by MySQL.');

        parent::testDropDefaultValue();
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::batchInsert()
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $columns, $values, $expected, $expectedParams, $insertedRow);
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::rawSql()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    public function testInsertEx(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertSame(
            ['id' => '4'],
            $command->insertEx('{{customer}}', ['name' => 'test_1', 'email' => 'test_1@example.com']),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        string $expected
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::upsert()
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }
}
