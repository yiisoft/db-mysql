<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Generator;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use function str_contains;
use function version_compare;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    public function testAddcheck(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::addCheck is not supported by MySQL.');

        $qb->addCheck('customer', 'id', 'id > 0');
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();
        $sql = <<<SQL
        ALTER TABLE `customer` CHANGE `id` `id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
        SQL;

        if (!str_contains($db->getServerVersion(), 'MariaDB') && version_compare($db->getServerVersion(), '8', '<')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
            SQL;
        } else {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key.'
            SQL;
        }

        $this->assertSame($sql, $qb->addCommentOnColumn('customer', 'id', 'Primary key.'));
    }

    /**
     * @throws Exception
     */
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
    }

    /**
     * @throws Exception
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::addDefaultValue is not supported by MySQL.');

        $qb->addDefaultValue('CN_pk', 'T_constraints_1', 'C_default', 1);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addForeignKey()
     */
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

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addPrimaryKey()
     */
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addUnique()
     */
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(string $table, array $columns, iterable|Generator $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildCondition()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildLikeCondition()
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildFrom()
     */
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
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
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildWhereExists()
     */
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWithWhereExists($cond, $expectedQuerySql);
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
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
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE `test` (
            \t`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            \t`name` varchar(255) NOT NULL,
            \t`email` varchar(255) NOT NULL,
            \t`status` int(11) NOT NULL,
            \t`created_at` datetime(0) NOT NULL
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
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::delete()
     */
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

        $qb->dropCheck('CN_check', 'T_constraints_1');
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/15500
     */
    public function testDefaultValues()
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        // Primary key columns should have NULL as value
        $this->assertSame(
            <<<SQL
            INSERT INTO `null_values` (`id`) VALUES (DEFAULT)
            SQL,
            $qb->insert('null_values', []),
        );

        // Non-primary key columns should have DEFAULT as value
        $this->assertSame(
            <<<SQL
            INSERT INTO `negative_default_values` (`tinyint_col`) VALUES (DEFAULT)
            SQL,
            $qb->insert('negative_default_values', []),
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();
        $sql = <<<SQL
        ALTER TABLE `customer` CHANGE `id` `id` int NOT NULL AUTO_INCREMENT COMMENT ''
        SQL;

        if (!str_contains($db->getServerVersion(), 'MariaDB') && version_compare($db->getServerVersion(), '8', '<')) {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT ''
            SQL;
        } else {
            $sql = <<<SQL
            ALTER TABLE `customer` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT COMMENT ''
            SQL;
        }

        $this->assertSame($sql, $qb->dropCommentFromColumn('customer', 'id'));
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
    }

    /**
     * @throws Exception
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::dropDefaultValue is not supported by MySQL.');

        $qb->dropDefaultValue('CN_pk', 'T_constraints_1');
    }

    public function testDropForeignKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `T_constraints_3` DROP FOREIGN KEY `CN_constraints_3`
            SQL,
            $qb->dropForeignKey('CN_constraints_3', 'T_constraints_3'),
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `T_constraints_1` DROP PRIMARY KEY
            SQL,
            $qb->dropPrimaryKey('CN_pk', 'T_constraints_1'),
        );
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DROP INDEX `test_uq_constraint` ON `test_uq`
            SQL,
            $qb->dropUnique('test_uq_constraint', 'test_uq', ['int1']),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::insert()
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::insertWithReturningPks()
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\DMLQueryBuilder::insertWithReturningPks is not supported by Mysql.'
        );

        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();
        $qb->insertWithReturningPks($table, $columns, $params);
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
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $checkSql = 'SHOW CREATE TABLE `item`;';

        // Change to max rows.
        $sql = <<<SQL
        SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
        SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
        PREPARE autoincrement_stmt FROM @sql;
        EXECUTE autoincrement_stmt
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item'));

        $command->setSql($sql)->execute();
        $result = $command->setSql($checkSql)->queryOne();

        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=6', array_values($result)[1]);

        // Key as string.
        $sql = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=40;
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item', '40'));

        $command->setSql($sql)->execute();
        $result = $command->setSql($checkSql)->queryOne();

        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=40', array_values($result)[1]);

        // Change up, key as int.
        $sql = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=40;
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item', 40));

        $db->createCommand($sql)->execute();
        $result = $command->setSql($checkSql)->queryOne();

        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=40', array_values($result)[1]);

        // And again change to max rows.
        $sql = <<<SQL
        SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
        SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
        PREPARE autoincrement_stmt FROM @sql;
        EXECUTE autoincrement_stmt
        SQL;

        $this->assertSame($sql, $qb->resetSequence('item'));

        $db->createCommand($sql)->queryAll();
        $result = $command->setSql($checkSql)->queryOne();

        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=6', array_values($result)[1]);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testUpsertExecute($table, $insertColumns, $updateColumns);
    }
}
