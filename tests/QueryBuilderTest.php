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
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Mysql\QueryBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestUtility\TestQueryBuilderTrait;

use function array_merge;
use function is_string;

/**
 * @group mysql
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @param bool $reset
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder(bool $reset = false): QueryBuilder
    {
        return new QueryBuilder($this->getConnection($reset));
    }

    public function testResetSequence(): void
    {
        $qb = $this->getQueryBuilder(true, true);

        $expected = 'ALTER TABLE `item` AUTO_INCREMENT=6';
        $sql = $qb->resetSequence('item');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE `item` AUTO_INCREMENT=4';
        $sql = $qb->resetSequence('item', 4);
        $this->assertEquals($expected, $sql);
    }

    public function testResetSequenceNoAssociated(): void
    {
        $qb = $this->getQueryBuilder(true, true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is no sequence associated with table 'constraints'");
        $sql = $qb->resetSequence('constraints');
    }

    public function testResetSequenceTableNoExist(): void
    {
        $qb = $this->getQueryBuilder(true, true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');
        $sql = $qb->resetSequence('noExist', 1);
    }

    public function addDropForeignKeysProvider(): array
    {
        $result = $this->addDropforeignKeysProviderTrait();

        $result['drop'][0] = 'ALTER TABLE {{T_constraints_3}} DROP FOREIGN KEY [[CN_constraints_3]]';

        return $result;
    }

    /**
     * @dataProvider addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    public function addDropPrimaryKeysProvider(): array
    {
        $result = $this->addDropPrimaryKeysProviderTrait();

        $result['drop'][0] = 'ALTER TABLE {{T_constraints_1}} DROP PRIMARY KEY';

        $result['add'][0] = 'ALTER TABLE {{T_constraints_1}} ADD CONSTRAINT [[CN_pk]] PRIMARY KEY ([[C_id_1]])';

        $result['add (2 columns)'][0] = 'ALTER TABLE {{T_constraints_1}} ADD CONSTRAINT [[CN_pk]]'
            . ' PRIMARY KEY ([[C_id_1]], [[C_id_2]])';

        return $result;
    }

    /**
     * @dataProvider addDropPrimaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    public function addDropUniquesProvider(): array
    {
        $result = $this->addDropUniquesProviderTrait();

        $result['drop'][0] = 'DROP INDEX [[CN_unique]] ON {{T_constraints_1}}';

        return $result;
    }

    /**
     * @dataProvider addDropUniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    /**
     * @dataProvider batchInsertProviderTrait
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string|null $expected
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, ?string $expected): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $sql = $queryBuilder->batchInsert($table, $columns, $value);

        $this->assertEquals($expected, $sql);
    }

    public function buildConditionsProvider(): array
    {
        return array_merge($this->buildConditionsProviderTrait(), [
            [
                ['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '{"lang":"uk","country":"UA"}'],
            ],
            [
                ['=', 'jsoncol', new JsonExpression([false])],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '[false]'],
            ],
            'object with type. Type is ignored for MySQL' => [
                ['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                '[[prices]] = CAST(:qp0 AS JSON)', [':qp0' => '{"seeds":15,"apples":25}'],
            ],
            'nested json' => [
                [
                    '=',
                    'data',
                    new JsonExpression(
                        [
                            'user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'],
                            'props' => ['mood' => 'good'],
                        ]
                    ),
                ],
                '[[data]] = CAST(:qp0 AS JSON)',
                [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
            ],
            'null value' => [
                ['=', 'jsoncol', new JsonExpression(null)],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => 'null'],
            ],
            'null as array value' => [
                ['=', 'jsoncol', new JsonExpression([null])],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '[null]'],
            ],
            'null as object value' => [
                ['=', 'jsoncol', new JsonExpression(['nil' => null])],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '{"nil":null}'],
            ],
            'query' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression((new Query($this->getConnection()))->select('params')->from('user')->where(['id' => 1])),
                ],
                '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)',
                [':qp0' => 1],
            ],
            'query with type, that is ignored in MySQL' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression((new Query($this->getConnection()))->select('params')->from('user')->where(['id' => 1]), 'jsonb'),
                ],
                '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)', [':qp0' => 1],
            ],
            'nested and combined json expression' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression(new JsonExpression(['a' => 1, 'b' => 2, 'd' => new JsonExpression(['e' => 3])])),
                ],
                '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '{"a":1,"b":2,"d":{"e":3}}'],
            ],
            'search by property in JSON column (issue #15838)' => [
                ['=', new Expression("(jsoncol->>'$.someKey')"), '42'],
                "(jsoncol->>'$.someKey') = :qp0", [':qp0' => 42],
            ],
        ]);
    }

    /**
     * @dataProvider buildConditionsProvider
     *
     * @param array|ExpressionInterface $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildCondition($condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildFilterConditionProviderTrait
     *
     * @param array $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, ?string $expected, array $expectedParams): void
    {
        $query = (new Query($this->getConnection()))->filterWhere($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildFromDataProviderTrait
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $params = [];

        $sql = $this->getQueryBuilder()->buildFrom([$table], $params);

        $this->assertEquals('FROM ' . $this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider buildLikeConditionsProviderTrait
     *
     * @param array|object $condition
     * @param string|null $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildLikeCondition($condition, ?string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildExistsParamsProviderTrait
     *
     * @param string $cond
     * @param string|null $expectedQuerySql
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildWhereExists(string $cond, ?string $expectedQuerySql): void
    {
        $db = $this->getConnection();

        $expectedQueryParams = [];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w');

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function createDropIndexesProvider(): array
    {
        $result = $this->createDropIndexesProviderTrait();

        $result['create'][0] = 'ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_single]] ([[C_index_1]])';

        $result['create (2 columns)'][0] = 'ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_multi]]'
            . ' ([[C_index_2_1]], [[C_index_2_2]])';

        $result['create unique'][0] = 'ALTER TABLE {{T_constraints_2}} ADD UNIQUE INDEX [[CN_constraints_2_single]]'
            . ' ([[C_index_1]])';

        $result['create unique (2 columns)'][0] = 'ALTER TABLE {{T_constraints_2}} ADD UNIQUE'
            . ' INDEX [[CN_constraints_2_multi]] ([[C_index_2_1]], [[C_index_2_2]])';

        return $result;
    }

    /**
     * @dataProvider createDropIndexesProvider
     *
     * @param string $sql
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    /**
     * @dataProvider deleteProviderTrait
     *
     * @param string $table
     * @param array|string $condition
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testDelete(string $table, $condition, ?string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->delete($table, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider insertProviderTrait
     *
     * @param string $table
     * @param array|ColumnSchema $columns
     * @param array $params
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testInsert(string $table, $columns, array $params, ?string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;

        $actualSQL = $this->getQueryBuilder()->insert($table, $columns, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function updateProvider(): array
    {
        $items = $this->updateProviderTrait();

        $items[] = [
            'profile',
            [
                'description' => new JsonExpression(['abc' => 'def', 123, null]),
            ],
            [
                'id' => 1,
            ],
            $this->replaceQuotes('UPDATE [[profile]] SET [[description]]=CAST(:qp0 AS JSON) WHERE [[id]]=:qp1'),
            [
                ':qp0' => '{"abc":"def","0":123,"1":null}',
                ':qp1' => 1,
            ],
        ];

        return $items;
    }

    /**
     * @dataProvider updateProvider
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string|null $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
        ?string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->update($table, $columns, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function upsertProvider(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)',
            ],
            'regular values with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`',
            ],
            'query' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)',
            ],
            'query with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1',
            ],
            'query without update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`',
            ],
            'values and expressions' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'query, values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `ts`=:qp1, [[orders]]=T_upsert.orders + 1',
            ],
            'query, values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `ts`=:qp1, [[orders]]=T_upsert.orders + 1',
            ],
            'no columns to update' => [
                3 => 'INSERT INTO `T_upsert_1` (`a`) VALUES (:qp0) ON DUPLICATE KEY UPDATE `a`=`T_upsert_1`.`a`',
            ],
        ];

        $newData = $this->upsertProviderTrait();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = \array_replace($newData[$testName], $data);
        }

        return $newData;
    }

    /**
     * @depends testInitFixtures
     *
     * @dataProvider upsertProvider
     *
     * @param string $table
     * @param array|ColumnSchema $insertColumns
     * @param array|bool|null $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws NotSupportedException
     * @throws Exception
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()
            ->upsert($table, $insertColumns, $updateColumns, $actualParams);

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

    public function testRenameColumn(): void
    {
        $qb = $this->getQueryBuilder();

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
        $qb = $this->getQueryBuilder();

        $this->expectException(Exception::class);
        $sql = $qb->renameColumn('noExist', 'string_identifier', 'string_identifier_test');
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

    public function testCommentTable(): void
    {
        $qb = $this->getQueryBuilder();

        $expected = "ALTER TABLE [[comment]] COMMENT 'This is my table.'";
        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "ALTER TABLE [[comment]] COMMENT ''";
        $sql = $qb->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function testCommentColumn(): void
    {
        $qb = $this->getQueryBuilder();

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

    public function testAddCheck(): void
    {
        $qb = $this->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\QueryBuilder::addCheck is not supported by MySQL.');
        $qb->addCheck('noExist', 'noExist', 'noExist');
    }

    public function testDropCheck(): void
    {
        $qb = $this->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Mysql\QueryBuilder::dropCheck is not supported by MySQL.');
        $qb->dropCheck('noExist', 'noExist');
    }
}
