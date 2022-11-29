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
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::batchInsert()
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
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::rawSql()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
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
