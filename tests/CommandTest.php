<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mysql\Tests\Provider\CommandProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
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

    #[DataProviderExternal(CommandProvider::class, 'batchInsert')]
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
        $db = $this->getConnection();
        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('MySQL doesn\'t support cascade drop table.');
        $command->dropTable('{{table}}', cascade: true);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'rawSql')]
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    public function testInsertReturningPksWithSubqueryAndNoAutoincrement(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $query = (new Query($db))->select(['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 4]);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\Command::insertReturningPks() is not supported by MySQL for tables without auto increment when inserting sub-query.'
        );

        $command->insertReturningPks('order_item', $query);
    }

    #[DataProviderExternal(CommandProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|ExpressionInterface|string $conditions,
        array|ExpressionInterface|string|null $from,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $from, $expectedValues, $expectedCount);
    }

    #[DataProviderExternal(CommandProvider::class, 'upsert')]
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
