<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\Tests\Provider\CommandProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CONVERT([[address]], CHAR)';

    public function testAddCheck(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableChecks is not supported by MySQL.');

        parent::testAddCheck();
    }

    public function testAddDefaultValue(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableDefaultValues is not supported by MySQL.');

        parent::testAddDefaultValue();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->alterColumn('{{customer}}', 'email', 'text')->execute();
        $schema = $db->getSchema();
        $columns = $schema->getTableSchema('{{customer}}')?->getColumns();

        $this->assertArrayHasKey('email', $columns);
        $this->assertSame('text', $columns['email']->getDbType());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::batchInsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $values, $columns, $expected, $expectedParams, $insertedRow);
    }

    public function testDropCheck(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableChecks is not supported by MySQL.');

        parent::testDropCheck();
    }

    public function testDropDefaultValue(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\Schema::loadTableDefaultValues is not supported by MySQL.');

        parent::testDropDefaultValue();
    }

    public function testDropTableCascade(): void
    {
        $command = $this->getConnection()->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('MySQL doesn\'t support cascade drop table.');
        $command->dropTable('{{table}}', cascade: true);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::rawSql
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
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::update
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expectedValues, $expectedCount);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\CommandProvider::upsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testShowDatabases(): void
    {
        $this->assertSame([self::getDatabaseName()], self::getDb()->createCommand()->showDatabases());
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, string|null $indexType, string|null $indexMethod): void
    {
        parent::testCreateIndex($columns, $indexColumns, $indexType, $indexMethod);
    }
}
