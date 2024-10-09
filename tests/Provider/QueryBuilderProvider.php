<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;

use function array_replace;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'mysql';

    public static function buildCondition(): array
    {
        return [
            ...parent::buildCondition(),
            [
                ['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])],
                '[[jsoncol]] = :qp0', [':qp0' => '{"lang":"uk","country":"UA"}'],
            ],
            [
                ['=', 'jsoncol', new JsonExpression([false])],
                '[[jsoncol]] = :qp0', [':qp0' => '[false]'],
            ],
            'object with type. Type is ignored for MySQL' => [
                ['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                '[[prices]] = :qp0', [':qp0' => '{"seeds":15,"apples":25}'],
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
                '[[data]] = :qp0',
                [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
            ],
            'null value' => [
                ['=', 'jsoncol', new JsonExpression(null)],
                '[[jsoncol]] = :qp0', [':qp0' => 'null'],
            ],
            'null as array value' => [
                ['=', 'jsoncol', new JsonExpression([null])],
                '[[jsoncol]] = :qp0', [':qp0' => '[null]'],
            ],
            'null as object value' => [
                ['=', 'jsoncol', new JsonExpression(['nil' => null])],
                '[[jsoncol]] = :qp0', [':qp0' => '{"nil":null}'],
            ],
            'query' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression((new Query(self::getDb()))->select('params')->from('user')->where(['id' => 1])),
                ],
                '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)',
                [':qp0' => 1],
            ],
            'query with type, that is ignored in MySQL' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression(
                        (new Query(self::getDb()))->select('params')->from('user')->where(['id' => 1]),
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
                '[[jsoncol]] = :qp0', [':qp0' => '{"a":1,"b":2,"d":{"e":3}}'],
            ],
            'search by property in JSON column (issue #15838)' => [
                ['=', new Expression("(jsoncol->>'$.someKey')"), '42'],
                "(jsoncol->>'$.someKey') = :qp0", [':qp0' => 42],
            ],
        ];
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO `customer` VALUES ()
        SQL;

        return $insert;
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ' .
                    'ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)',
            ],
            'regular values with unique at not the first position' => [
                3 => 'INSERT INTO `T_upsert` (`address`, `email`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ' .
                    'ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)',
            ],
            'regular values with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ' .
                    'ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)',
            ],
            'query' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` ' .
                    'WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)',
            ],
            'query with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` ' .
                    'WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1',
            ],
            'query without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` ' .
                    'WHERE `name`=:qp0 LIMIT 1',
            ],
            'values and expressions' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP) ' .
                    'ON DUPLICATE KEY UPDATE `ts`=VALUES(`ts`)',
            ],
            'values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP) ' .
                    'ON DUPLICATE KEY UPDATE `orders`=T_upsert.orders + 1',
            ],
            'values and expressions without update part' => [
                3 => 'INSERT IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)',
            ],
            'query, values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]] ' .
                    'ON DUPLICATE KEY UPDATE `ts`=:qp1, `orders`=T_upsert.orders + 1',
            ],
            'query, values and expressions without update part' => [
                3 => 'INSERT IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]',
            ],
            'no columns to update' => [
                3 => 'INSERT IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0)',
            ],
            'no columns to update with unique' => [
                3 => 'INSERT IGNORE INTO {{%T_upsert}} (`email`) VALUES (:qp0)',
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} (`type`) VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }

    public static function buildColumnDefinition(): array
    {
        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'int PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UPK][0] = 'int UNSIGNED PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::BIGPK][0] = 'bigint PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UBIGPK][0] = 'bigint UNSIGNED PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UUID_PK][0] = 'binary(16) PRIMARY KEY DEFAULT uuid_to_bin(uuid())';
        $values[PseudoType::UUID_PK_SEQ][0] = 'binary(16) PRIMARY KEY DEFAULT uuid_to_bin(uuid())';
        $values['primaryKey()'][0] = 'int PRIMARY KEY AUTO_INCREMENT';
        $values['primaryKey(false)'][0] = 'int PRIMARY KEY';
        $values['smallPrimaryKey()'][0] = 'smallint PRIMARY KEY AUTO_INCREMENT';
        $values['bigPrimaryKey()'][0] = 'bigint PRIMARY KEY AUTO_INCREMENT';
        $values['uuidPrimaryKey()'][0] = 'binary(16) PRIMARY KEY DEFAULT uuid_to_bin(uuid())';
        $values['uuidPrimaryKey(false)'][0] = 'binary(16) PRIMARY KEY';
        $values['boolean()'][0] = 'bit(1)';
        $values['boolean(100)'][0] = 'bit(1)';
        $values['integer()'][0] = 'int';
        $values['integer(8)'][0] = 'int(8)';
        $values['money()'][0] = 'decimal(19,4)';
        $values['money(10)'][0] = 'decimal(10,4)';
        $values['money(10,2)'][0] = 'decimal(10,2)';
        $values['money(null)'][0] = 'decimal';
        $values['binary()'][0] = 'blob';
        $values['binary(1000)'][0] = 'blob(1000)';
        $values['uuid()'][0] = 'binary(16)';
        $values["comment('comment')"][0] = "varchar(255) COMMENT 'comment'";
        $values["comment('')"][0] = "varchar(255) COMMENT ''";
        $values['integer()->primaryKey()'][0] = 'int PRIMARY KEY';
        $values["integer()->defaultValue('')"][0] = 'int';
        $values['unsigned()'][0] = 'int UNSIGNED';
        $values['integer(8)->scale(2)'][0] = 'int(8)';
        $values['reference($reference)'][0] = 'int REFERENCES `ref_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';

        return $values;
    }
}
