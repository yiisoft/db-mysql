<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Closure;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\TestSupport\TestQueryBuilderTrait;

use function is_string;

/**
 * @group mysql
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, ?string $expected, array $expectedParams = []): void
    {
        $params = [];
        $db = $this->getConnection();

        $sql = $db->getQueryBuilder()->batchInsert($table, $columns, $value, $params);

        $this->assertEquals($expected, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildCondition(array|\Yiisoft\Db\Expression\ExpressionInterface|string $condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildFilterConditionProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->filterWhere($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildFromDataProvider
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $db = $this->getConnection();
        $params = [];
        $sql = $db->getQueryBuilder()->buildFrom([$table], $params);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('FROM ' . $replacedQuotes, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildLikeConditionsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildLikeCondition(array|ExpressionInterface $condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildExistsParamsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildWhereExists(string $cond, ?string $expectedQuerySql): void
    {
        $db = $this->getConnection();
        $expectedQueryParams = [];
        $subQuery = new Query($db);
        $subQuery->select('1')->from('Website w');
        $query = new Query($db);
        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testCheckIntegrity(): void
    {
        $this->assertEqualsWithoutLE(
            <<<SQL
            SET FOREIGN_KEY_CHECKS = 1
            SQL,
            $this->getConnection()->getQueryBuilder()->checkIntegrity('dbo', 'item'),
        );
    }

    public function testCheckIntegrityExecute(): void
    {
        $db = $this->getConnection();
        $db->createCommand()->checkIntegrity('public', 'item', false)->execute();
        $sql = 'INSERT INTO {{item}}([[name]], [[category_id]]) VALUES (\'invalid\', 99999)';
        $command = $db->createCommand($sql);
        $command->execute();
        $db->createCommand()->checkIntegrity('public', 'item', true)->execute();
        $this->expectException(IntegrityException::class);
        $command->execute();
    }

    public function testCommentColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = 'ALTER TABLE [[comment]] CHANGE [[add_comment]] [[add_comment]]' .
            " varchar(255) NOT NULL COMMENT 'This is my column.'";
        $sql = $qb->addCommentOnColumn('comment', 'add_comment', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = 'ALTER TABLE [[comment]] CHANGE [[replace_comment]] [[replace_comment]]' .
            " varchar(255) DEFAULT NULL COMMENT 'This is my column.'";
        $sql = $qb->addCommentOnColumn('comment', 'replace_comment', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = 'ALTER TABLE [[comment]] CHANGE [[delete_comment]] [[delete_comment]]' .
            " varchar(128) NOT NULL COMMENT ''";
        $sql = $qb->dropCommentFromColumn('comment', 'delete_comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function testCommentTable(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = "ALTER TABLE [[comment]] COMMENT 'This is my table.'";
        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "ALTER TABLE [[comment]] COMMENT ''";
        $sql = $qb->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::createDropIndexesProvider
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::deleteProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testDelete(string $table, array|string $condition, ?string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->delete($table, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\DDLQueryBuilder::dropCheck is not supported by MySQL.');
        $db->getQueryBuilder()->dropCheck('noExist', 'noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::insertProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        ?string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = $params;
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->insert($table, $columns, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $sql = $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test');
        $this->assertSame(
            'ALTER TABLE `alpha` CHANGE `string_identifier` `string_identifier_test` varchar(255) NOT NULL',
            $sql,
        );

        $sql = $qb->renameColumn('alpha', 'string_identifier_test', 'string_identifier');
        $this->assertSame(
            'ALTER TABLE `alpha` CHANGE `string_identifier_test` `string_identifier`',
            $sql,
        );
    }

    public function testRenameColumnTableNoExist(): void
    {
        $this->markTestSkipped('In current implementation not generated Exception if table not exists');
        $db = $this->getConnection();
        $this->expectException(Exception::class);
        $sql = $db->getQueryBuilder()->renameColumn('noExist', 'string_identifier', 'string_identifier_test');
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $checkSql = 'SHOW CREATE TABLE `item`;';

        // change to max rows
        $expected = <<<SQL
SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
PREPARE autoincrement_stmt FROM @sql;
EXECUTE autoincrement_stmt
SQL;
        $sql = $qb->resetSequence('item');
        $this->assertSame($expected, $sql);

        $db->createCommand($sql)->execute();
        $result = $db->createCommand($checkSql)->queryOne();
        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=6', array_values($result)[1]);

        // change up
        $expected = 'ALTER TABLE `item` AUTO_INCREMENT=40;';
        $sql = $qb->resetSequence('item', 40);
        $this->assertSame($expected, $sql);

        $db->createCommand($sql)->execute();
        $result = $db->createCommand($checkSql)->queryOne();
        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=40', array_values($result)[1]);

        // and again change to max rows
        $expected = <<<SQL
SET @new_autoincrement_value := (SELECT MAX(`id`) + 1 FROM `item`);
SET @sql = CONCAT('ALTER TABLE `item` AUTO_INCREMENT =', @new_autoincrement_value);
PREPARE autoincrement_stmt FROM @sql;
EXECUTE autoincrement_stmt
SQL;
        $sql = $qb->resetSequence('item');
        $this->assertSame($expected, $sql);

        $db->createCommand($sql)->queryAll();
        $result = $db->createCommand($checkSql)->queryOne();
        $this->assertIsArray($result);
        $this->assertStringContainsString('AUTO_INCREMENT=6', array_values($result)[1]);
    }

    public function testResetSequenceNoAssociated(): void
    {
        $db = $this->getConnection();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is no sequence associated with table 'constraints'");
        $sql = $db->getQueryBuilder()->resetSequence('constraints');
    }

    public function testResetSequenceTableNoExist(): void
    {
        $db = $this->getConnection();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');
        $sql = $db->getQueryBuilder()->resetSequence('noExist', 1);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::updateProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        ?string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->update($table, $columns, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::upsertProvider
     *
     * @param string|string[] $expectedSQL
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpsert(string $table, array|QueryInterface $insertColumns, array|bool $updateColumns, string|array $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertSame($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            $this->assertIsOneOf($actualParams, $expectedParams);
        }
    }
}
