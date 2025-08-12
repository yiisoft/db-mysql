<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use function str_contains;
use function version_compare;

/**
 * @group mysql
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

    public function testAddcheck(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::addCheck is not supported by MySQL.');

        $qb->addCheck('id', 'customer', 'id > 0');

        $db->close();
    }

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection(true);
        $serverVersion = $db->getServerInfo()->getVersion();

        $qb = $db->getQueryBuilder();
        $sql = <<<SQL
        ALTER TABLE `customer` CHANGE `id` `id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
        SQL;

        if (!str_contains($serverVersion, 'MariaDB') && version_compare($serverVersion, '8', '<')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
            SQL;
        } elseif (str_contains($serverVersion, 'MariaDB')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
            SQL;
        }

        $this->assertSame($sql, $qb->addCommentOnColumn('customer', 'id', 'Primary key.'));

        $db->close();
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable('customer', 'Customer table.');

        $this->assertSame(
            <<<SQL
            ALTER TABLE `customer` COMMENT 'Customer table.'
            SQL,
            $sql,
        );

        $db->close();
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::addDefaultValue is not supported by MySQL.');

        $qb->addDefaultValue('T_constraints_1', 'CN_pk', 'C_default', 1);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addForeignKey')]
    public function testAddForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string|null $delete,
        string|null $update,
        string $expected
    ): void {
        parent::testAddForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addPrimaryKey')]
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addUnique')]
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'alterColumn')]
    public function testAlterColumn(string|ColumnInterface $type, string $expected): void
    {
        parent::testAlterColumn($type, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        parent::testBatchInsert($table, $rows, $columns, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildLikeCondition')]
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildFrom')]
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    public function testBuildWithOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * LIMIT 10, 18446744073709551615
            SQL,
            $sql,
        );

        $this->assertSame([], $params);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildWhereExists')]
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWithWhereExists($cond, $expectedQuerySql);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            SET FOREIGN_KEY_CHECKS = 1
            SQL,
            $qb->checkIntegrity('', 'customer'),
        );

        $db->close();
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE `test` (
            \t`id` int PRIMARY KEY AUTO_INCREMENT,
            \t`name` varchar(255) NOT NULL,
            \t`email` varchar(255) NOT NULL,
            \t`status` integer NOT NULL,
            \t`created_at` datetime NOT NULL
            )
            SQL,
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => 'string(255) NOT NULL',
                    'email' => 'string(255) NOT NULL',
                    'status' => 'integer NOT NULL',
                    'created_at' => 'datetime NOT NULL',
                ],
            ),
        );

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'delete')]
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::dropCheck is not supported by MySQL.');

        $qb->dropCheck('T_constraints_1', 'CN_check');

        $db->close();
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/15500
     */
    public function testDefaultValues(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        // Primary key columns should have NULL as value
        $this->assertSame(
            <<<SQL
            INSERT INTO `null_values` VALUES ()
            SQL,
            $qb->insert('null_values', []),
        );

        // Non-primary key columns should have DEFAULT as value
        $this->assertSame(
            <<<SQL
            INSERT INTO `negative_default_values` VALUES ()
            SQL,
            $qb->insert('negative_default_values', []),
        );

        $db->close();
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);
        $serverVersion = $db->getServerInfo()->getVersion();

        $qb = $db->getQueryBuilder();
        $sql = <<<SQL
        ALTER TABLE `customer` CHANGE `id` `id` int NOT NULL AUTO_INCREMENT COMMENT ''
        SQL;

        if (!str_contains($serverVersion, 'MariaDB') && version_compare($serverVersion, '8', '<')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT ''
            SQL;
        } elseif (str_contains($serverVersion, 'MariaDB')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT ''
            SQL;
        }

        $this->assertSame($sql, $qb->dropCommentFromColumn('customer', 'id'));

        $db->close();
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `customer` COMMENT ''
            SQL,
            $qb->dropCommentFromTable('customer'),
        );

        $db->close();
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::dropDefaultValue is not supported by MySQL.');

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');

        $db->close();
    }

    public function testDropForeignKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `T_constraints_3` DROP FOREIGN KEY `CN_constraints_3`
            SQL,
            $qb->dropForeignKey('T_constraints_3', 'CN_constraints_3'),
        );

        $db->close();
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `T_constraints_1` DROP PRIMARY KEY
            SQL,
            $qb->dropPrimaryKey('T_constraints_1', 'CN_pk'),
        );

        $db->close();
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DROP INDEX `test_uq_constraint` ON `test_uq`
            SQL,
            $qb->dropUnique('test_uq', 'test_uq_constraint'),
        );

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insert')]
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::insertReturningPks is not supported by MySQL.'
        );

        $qb->insertReturningPks($table, $columns, $params);

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14663
     */
    public function testInsertInteger()
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        // Integer value should not be converted to string, when column is `int`.
        $this->assertSame(
            <<<SQL
            INSERT INTO `type` (`int_col`) VALUES (22)
            SQL,
            $command->insert('{{type}}', ['int_col' => 22])->getRawSql(),
        );

        // Integer value should not be converted to string, when column is `int unsigned`.
        $sql = $command->insert('{{type}}', ['int_col3' => 22])->getRawSql();
        $this->assertEquals('INSERT INTO `type` (`int_col3`) VALUES (22)', $sql);

        // int value should not be converted to string, when column is `bigint unsigned`.
        $this->assertEquals(
            <<<SQL
            INSERT INTO `type` (`bigint_col`) VALUES (22)
            SQL,
            $command->insert('{{type}}', ['bigint_col' => 22])->getRawSql(),
        );

        // string value should not be converted
        $this->assertEquals(
            <<<SQL
            INSERT INTO `type` (`bigint_col`) VALUES ('1000000000000')
            SQL,
            $command->insert('{{type}}', ['bigint_col' => '1000000000000'])->getRawSql(),
        );

        $db->close();
    }

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` CHANGE `string_identifier` `string_identifier_test` varchar(255) NOT NULL
            SQL,
            $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` CHANGE `non_exist_column` `new_column`
            SQL,
            $qb->renameColumn('alpha', 'non_exist_column', 'new_column'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE `non_exist_table` CHANGE `non_exist_column` `new_column`
            SQL,
            $qb->renameColumn('non_exist_table', 'non_exist_column', 'new_column'),
        );

        $db->close();
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();

        // Change to max rows.
        $sql = <<<SQL
        SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
        SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
        PREPARE autoincrement_stmt FROM @sql;
        EXECUTE autoincrement_stmt
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item'));

        $command->setSql($sql)->execute();
        $insertResult = $command->insertReturningPks('item', ['name' => '123', 'category_id' => 1]);
        $this->assertEquals(6, $insertResult['id']);

        // Key as string.
        $sql = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=40;
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item', '40'));

        $command->setSql($sql)->execute();
        $insertResult = $command->insertReturningPks('item', ['name' => '123', 'category_id' => 1]);
        $this->assertEquals(40, $insertResult['id']);

        // Change up, key as int.
        $sql = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=43;
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item', 43));

        $db->createCommand($sql)->execute();
        $insertResult = $command->insertReturningPks('item', ['name' => '123', 'category_id' => 1]);
        $this->assertEquals(43, $insertResult['id']);

        $command->delete('item', ['>=','id', 6])->execute();
        // And again change to max rows.
        $sql = <<<SQL
        SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
        SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
        PREPARE autoincrement_stmt FROM @sql;
        EXECUTE autoincrement_stmt
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item'));

        $db->createCommand($sql)->execute();
        $insertResult = $command->insertReturningPks('item', ['name' => '123', 'category_id' => 1]);
        $this->assertEquals(6, $insertResult['id']);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams,
    ): void {
        parent::testUpdate($table, $columns, $condition, $params, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|null $returnColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        parent::testUpsertReturning($table, $insertColumns, $updateColumns, $returnColumns, $expectedSql, $expectedParams);
    }

    #[TestWith(['order', ['id' => 1], ['id' => 10]])]
    #[TestWith(['without_pk', ['email' => 'test@example.com'], ['email' => 'info@example.com']])]
    public function testUpsertReturningWithUpdatingPrimaryKeyOrUnique(
        string $table,
        array $insertColumns,
        array $updateColumns,
    ): void {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL when updating primary key or unique values.'
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns);
    }

    #[TestWith(['order_item', ['subtotal' => 1], ['subtotal' => 10]])]
    #[TestWith(['without_pk', ['email' => null, 'name' => 'John'], ['name' => 'John']])]
    public function testUpsertReturningWithNullPrimaryKeyOrUnique(
        string $table,
        array $insertColumns,
        array $updateColumns,
    ): void {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL when inserting `null` primary key or unique values.'
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns);
    }

    public function testUpsertReturningWithSubqueryAndNoAutoincrement(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $query = (new Query($db))->select(['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 4]);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL for tables without auto increment when inserting sub-query.'
        );

        $qb->upsertReturning('order_item', $query);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'selectScalar')]
    public function testSelectScalar(array|bool|float|int|string $columns, string $expected): void
    {
        parent::testSelectScalar($columns, $expected);
    }

    public function testJsonOverlapsBuilder(): void
    {
        $db = $this->getConnection();
        $serverVersion = $db->getServerInfo()->getVersion();

        if (str_contains($serverVersion, 'MariaDB') && version_compare($serverVersion, '10.9', '<')) {
            self::markTestSkipped('MariaDB < 10.9 does not support JSON_OVERLAPS() function.');
        } elseif (version_compare($serverVersion, '8', '<')) {
            self::markTestSkipped('MySQL < 8 does not support JSON_OVERLAPS() function.');
        }

        $qb = $db->getQueryBuilder();

        $params = [];
        $sql = $qb->buildExpression(new JsonOverlaps('column', [1, 2, 3]), $params);

        $this->assertSame('JSON_OVERLAPS(`column`, :qp0)', $sql);
        $this->assertEquals([':qp0' => new Param('[1,2,3]', DataType::STRING)], $params);

        // Test column as Expression
        $params = [];
        $sql = $qb->buildExpression(new JsonOverlaps(new Expression('column'), [1, 2, 3]), $params);

        $this->assertSame('JSON_OVERLAPS(column, :qp0)', $sql);
        $this->assertEquals([':qp0' => new Param('[1,2,3]', DataType::STRING)], $params);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'overlapsCondition')]
    public function testJsonOverlaps(iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getConnection();
        $serverVersion = $db->getServerInfo()->getVersion();

        if (str_contains($serverVersion, 'MariaDB') && version_compare($serverVersion, '10.9', '<')) {
            $db->close();
            self::markTestSkipped('MariaDB < 10.9 does not support JSON_OVERLAPS() function.');
        } elseif (version_compare($serverVersion, '8', '<')) {
            $db->close();
            self::markTestSkipped('MySQL < 8 does not support JSON_OVERLAPS() function.');
        }

        $count = (new Query($db))
            ->from('json_type')
            ->where(new JsonOverlaps('json_col', $values))
            ->count();

        $this->assertSame($expectedCount, $count);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'overlapsCondition')]
    public function testJsonOverlapsOperator(iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getConnection();
        $serverVersion = $db->getServerInfo()->getVersion();

        if (str_contains($serverVersion, 'MariaDB') && version_compare($serverVersion, '10.9', '<')) {
            $db->close();
            self::markTestSkipped('MariaDB < 10.9 does not support JSON_OVERLAPS() function.');
        } elseif (version_compare($serverVersion, '8', '<')) {
            $db->close();
            self::markTestSkipped('MySQL < 8 does not support JSON_OVERLAPS() function.');
        }

        $count = (new Query($db))
            ->from('json_type')
            ->where(['json overlaps', 'json_col', $values])
            ->count();

        $this->assertSame($expectedCount, $count);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildColumnDefinition')]
    public function testBuildColumnDefinition(string $expected, ColumnInterface|string $column): void
    {
        parent::testBuildColumnDefinition($expected, $column);
    }

    #[DataProvider('dataDropTable')]
    public function testDropTable(string $expected, ?bool $ifExists, ?bool $cascade): void
    {
        if ($cascade) {
            $qb = $this->getConnection()->getQueryBuilder();

            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage('MySQL doesn\'t support cascade drop table.');

            $ifExists === null
                ? $qb->dropTable('customer', cascade: true)
                : $qb->dropTable('customer', ifExists: $ifExists, cascade: true);

            return;
        }

        parent::testDropTable($expected, $ifExists, $cascade);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareValue')]
    public function testPrepareValue(string $expected, mixed $value): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->assertSame($expected, $qb->prepareValue($value));
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseExpressionBuilder')]
    public function testCaseExpressionBuilder(
        CaseExpression $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseExpressionBuilder($case, $expectedSql, $expectedParams, $expectedResult);
    }
}
