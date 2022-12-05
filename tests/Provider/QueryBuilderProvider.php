<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Provider\BaseQueryBuilderProvider;

use function array_replace;

final class QueryBuilderProvider
{
    use TestTrait;

    public function addForeignKey(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addForeignKey($this->getDriverName());
    }

    public function addPrimaryKey(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addPrimaryKey($this->getDriverName());
    }

    public function addUnique(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addUnique($this->getDriverName());
    }

    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->batchInsert($this->getDriverName());
    }

    public function buildCondition(): array
    {
        $db = $this->getConnection();

        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $buildCondition = $baseQueryBuilderProvider->buildCondition($db);

        return array_merge(
            $buildCondition,
            [
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
                        new JsonExpression((new Query($db))->select('params')->from('user')->where(['id' => 1])),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)',
                    [':qp0' => 1],
                ],
                'query with type, that is ignored in MySQL' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            (new Query($db))->select('params')->from('user')->where(['id' => 1]),
                            'jsonb'
                        ),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)', [':qp0' => 1],
                ],
                'nested and combined json expression' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            new JsonExpression(['a' => 1, 'b' => 2, 'd' => new JsonExpression(['e' => 3])])
                        ),
                    ],
                    '[[jsoncol]] = CAST(:qp0 AS JSON)', [':qp0' => '{"a":1,"b":2,"d":{"e":3}}'],
                ],
                'search by property in JSON column (issue #15838)' => [
                    ['=', new Expression("(jsoncol->>'$.someKey')"), '42'],
                    "(jsoncol->>'$.someKey') = :qp0", [':qp0' => 42],
                ],
            ],
        );
    }

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFrom($this->getDriverName());
    }

    public function buildLikeCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildLikeCondition($this->getDriverName());
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildWhereExists($this->getDriverName());
    }

    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->delete($this->getDriverName());
    }

    public function insert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insert($this->getConnection());
    }

    public function insertEx(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insertEx($this->getConnection());
    }

    public function update(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->update($this->getDriverName());
    }

    public function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`
                SQL,
            ],
            'values and expressions' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `ts`=:qp1, [[orders]]=T_upsert.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `ts`=:qp1, [[orders]]=T_upsert.orders + 1
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                INSERT INTO `T_upsert_1` (`a`) VALUES (:qp0) ON DUPLICATE KEY UPDATE `a`=`T_upsert_1`.`a`
                SQL,
            ],
        ];

        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();
        $upsert = $baseQueryBuilderProvider->upsert($this->getConnection());

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
