<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Closure;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\TestSupport\TestQueryBuilderTrait;

use function array_merge;
use function is_string;

/**
 * @group mysql
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string|null $expected
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, ?string $expected): void
    {
        $db = $this->getConnection();
        $sql = $db->getQueryBuilder()->batchInsert($table, $columns, $value);
        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @param array|ExpressionInterface $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildCondition($condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildFilterConditionProvider
     *
     * @param array $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->filterWhere($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildFromDataProvider
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $db = $this->getConnection();
        $params = [];
        $sql = $db->getQueryBuilder()->buildFrom([$table], $params);
        $this->assertEquals('FROM ' . $this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildLikeConditionsProvider
     *
     * @param array|object $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildLikeCondition($condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::buildExistsParamsProvider
     *
     * @param string $cond
     * @param string|null $expectedQuerySql
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
     *
     * @param string $sql
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::deleteProvider
     *
     * @param string $table
     * @param array|string $condition
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testDelete(string $table, $condition, ?string $expectedSQL, array $expectedParams): void
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
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mysql\PDO\QueryBuilderPDOMysql::dropCheck is not supported by MySQL.'
        );
        $db->getQueryBuilder()->dropCheck('noExist', 'noExist');
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QueryBuilderProvider::insertProvider
     *
     * @param string $table
     * @param array|ColumnSchema $columns
     * @param array $params
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testInsert(
        string $table,
        $columns,
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
        $db = $this->getConnection();
        $this->expectException(Exception::class);
        $sql = $db->getQueryBuilder()->renameColumn('noExist', 'string_identifier', 'string_identifier_test');
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $expected = 'ALTER TABLE `item` AUTO_INCREMENT=6';
        $sql = $qb->resetSequence('item');
        $this->assertSame($expected, $sql);

        $expected = 'ALTER TABLE `item` AUTO_INCREMENT=4';
        $sql = $qb->resetSequence('item', 4);
        $this->assertSame($expected, $sql);
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
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
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
     * @param string $table
     * @param array|ColumnSchema $insertColumns
     * @param array|bool|null $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
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
