<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Assert;

use function str_contains;
use function version_compare;

/**
 * @group mysql
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use IntegrationTestTrait;

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

    public function testAddCheck(): void
    {
        $db = $this->getSharedConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::addCheck is not supported by MySQL.');

        $qb->addCheck('id', 'customer', 'id > 0');

        $db->close();
    }

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();
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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        ?string $delete,
        ?string $update,
        string $expected,
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
        Closure|array|ExpressionInterface|string $condition,
        ?string $expected,
        array $expectedParams,
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildLikeCondition')]
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams,
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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();
        $this->loadFixture();
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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::dropDefaultValue is not supported by MySQL.');

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');

        $db->close();
    }

    public function testDropForeignKey(): void
    {
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();

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
        Closure|array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        Closure|array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::insertReturningPks is not supported by MySQL.',
        );

        $qb->insertReturningPks($table, $columns, $params);

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14663
     */
    public function testInsertInteger()
    {
        $db = $this->getSharedConnection();

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
        $db = $this->getSharedConnection();
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
        $db = $this->getSharedConnection();
        $this->loadFixture();

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
        array|ExpressionInterface|string $condition,
        Closure|array|ExpressionInterface|string|null $from,
        array $params,
        string $expectedSql,
        array $expectedParams = [],
    ): void {
        parent::testUpdate($table, $columns, $condition, $from, $params, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        Closure|array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSql,
        array $expectedParams,
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        Closure|array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        ?array $returnColumns,
        string $expectedSql,
        array $expectedParams,
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
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL when updating different primary key or unique values.',
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns);
    }

    public function testUpsertReturningWithSameUpdatingPrimaryKeyOrUnique(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $qb = $db->getQueryBuilder();

        $params = [];
        $sql = $qb->upsertReturning('category', ['id' => 1, 'name' => 'Books'], ['id' => 1, 'name' => 'Audio'], params: $params);

        $this->assertSame(
            'INSERT INTO `category` (`id`, `name`)'
            . ' SELECT `id`, `name` FROM (SELECT 1 AS `id`, :qp0 AS `name`) AS EXCLUDED'
            . ' ON DUPLICATE KEY UPDATE `name`=:qp1, `id`=LAST_INSERT_ID(`category`.`id`);'
            . 'SELECT `id`, `name` FROM `category` WHERE `id` = LAST_INSERT_ID()',
            $sql,
        );

        $this->assertEquals(
            [
                ':qp0' => new Param('Books', DataType::STRING),
                ':qp1' => new Param('Audio', DataType::STRING),
            ],
            $params,
        );
    }

    #[TestWith(['order_item', ['subtotal' => 1], ['subtotal' => 10]])]
    #[TestWith(['without_pk', ['email' => null, 'name' => 'John'], ['name' => 'John']])]
    public function testUpsertReturningWithNullPrimaryKeyOrUnique(
        string $table,
        array $insertColumns,
        array $updateColumns,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL when inserting `null` primary key or unique values.',
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns);
    }

    public function testUpsertReturningWithSubqueryAndNoAutoincrement(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();
        $qb = $db->getQueryBuilder();

        $query = (new Query($db))->select(['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 4]);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::upsertReturning() is not supported by MySQL for tables without auto increment when inserting sub-query.',
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
        $db = $this->getSharedConnection();
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
    public function testJsonOverlaps(Closure|iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getSharedConnection();
        $serverVersion = $db->getServerInfo()->getVersion();

        if ($values instanceof Closure) {
            $values = $values($db);
        }

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
    public function testJsonOverlapsOperator(Closure|iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getSharedConnection();

        if ($values instanceof Closure) {
            $values = $values($db);
        }

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
    public function testBuildColumnDefinition(string $expected, Closure|ColumnInterface|string $column): void
    {
        parent::testBuildColumnDefinition($expected, $column);
    }

    #[DataProvider('dataDropTable')]
    public function testDropTable(string $expected, ?bool $ifExists, ?bool $cascade): void
    {
        if ($cascade) {
            $qb = $this->getSharedConnection()->getQueryBuilder();

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
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $this->assertSame($expected, $qb->prepareValue($value));
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseXBuilder')]
    public function testCaseXBuilder(
        Closure|CaseX $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseXBuilder($case, $expectedSql, $expectedParams, $expectedResult);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'lengthBuilder')]
    public function testLengthBuilder(
        Closure|string|ExpressionInterface $operand,
        string $expectedSql,
        int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testLengthBuilder($operand, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionBuilder')]
    public function testMultiOperandFunctionBuilder(
        string $class,
        Closure|array $operands,
        string $expectedSql,
        array|string|int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testMultiOperandFunctionBuilder($class, $operands, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionClasses')]
    public function testMultiOperandFunctionBuilderWithoutOperands(string $class): void
    {
        parent::testMultiOperandFunctionBuilderWithoutOperands($class);
    }

    #[TestWith(['int[]', 'int', '[1,2,3,4,5,6,7]'])]
    #[TestWith([new IntegerColumn(), 'int', '[1,2,3,4,5,6,7]'])]
    #[TestWith([new ArrayColumn(), 'json', '["1","2","3","4","5","6","7"]'])]
    #[TestWith([new ArrayColumn(column: new IntegerColumn()), 'int', '[1,2,3,4,5,6,7]'])]
    public function testArrayMergeWithTypeWithOrdering(
        string|ColumnInterface $type,
        string $operandType,
        string $expectedResult,
    ): void {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();
        $serverVersion = $db->getServerInfo()->getVersion();

        $isMariadb = str_contains($serverVersion, 'MariaDB');

        if (
            $isMariadb && version_compare($serverVersion, '10.6', '<')
            || !$isMariadb && version_compare($serverVersion, '8.0.0', '<')
        ) {
            $this->markTestSkipped('MariaDB < 10.6 and MySQL < 8 does not support JSON_TABLE() function.');
        }

        $stringParam = new Param('[4,3,5]', DataType::STRING);
        $arrayMerge = (new ArrayMerge(
            [2, 1, 3],
            new ArrayValue([6, 5, 7]),
            $stringParam,
        ))->type($type)->ordered();
        $params = [];

        $this->assertSame(
            '(SELECT JSON_ARRAYAGG(value) AS value FROM ('
            . "SELECT value FROM JSON_TABLE(:qp0, '$[*]' COLUMNS(value $operandType PATH '$')) AS t"
            . " UNION SELECT value FROM JSON_TABLE(:qp1, '$[*]' COLUMNS(value $operandType PATH '$')) AS t"
            . " UNION SELECT value FROM JSON_TABLE(:qp2, '$[*]' COLUMNS(value $operandType PATH '$')) AS t"
            . ' ORDER BY value) AS t)',
            $qb->buildExpression($arrayMerge, $params),
        );
        Assert::arraysEquals(
            [
                ':qp0' => new Param('[2,1,3]', DataType::STRING),
                ':qp1' => new Param('[6,5,7]', DataType::STRING),
                ':qp2' => $stringParam,
            ],
            $params,
        );

        $result = $db->select($arrayMerge)->scalar();

        $this->assertEquals(json_decode($expectedResult), json_decode($result));
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertWithMultiOperandFunctions')]
    public function testUpsertWithMultiOperandFunctions(
        array $initValues,
        array|QueryInterface $insertValues,
        array $updateValues,
        string $expectedSql,
        array $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testUpsertWithMultiOperandFunctions($initValues, $insertValues, $updateValues, $expectedSql, $expectedResult, $expectedParams);
    }
}
