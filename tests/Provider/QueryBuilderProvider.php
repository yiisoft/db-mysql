<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

use function array_replace;
use function str_contains;
use function version_compare;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'mysql';

    public static function alterColumn(): array
    {
        return [
            [ColumnType::STRING, 'ALTER TABLE `foo1` CHANGE `bar` `bar` varchar(255)'],
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
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`)'
                    . ' SELECT `email`, `address`, `status`, `profile_id`'
                    . ' FROM (SELECT :qp0 AS `email`, :qp1 AS `address`, :qp2 AS `status`, :qp3 AS `profile_id`) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `address`=EXCLUDED.`address`, `status`=EXCLUDED.`status`,'
                    . ' `profile_id`=EXCLUDED.`profile_id`',
            ],
            'regular values with unique at not the first position' => [
                3 => 'INSERT INTO `T_upsert` (`address`, `email`, `status`, `profile_id`)'
                    . ' SELECT `address`, `email`, `status`, `profile_id`'
                    . ' FROM (SELECT :qp0 AS `address`, :qp1 AS `email`, :qp2 AS `status`, :qp3 AS `profile_id`) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `address`=EXCLUDED.`address`, `status`=EXCLUDED.`status`,'
                    . ' `profile_id`=EXCLUDED.`profile_id`',
            ],
            'regular values with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`)'
                    . ' SELECT `email`, `address`, `status`, `profile_id`'
                    . ' FROM (SELECT :qp0 AS `email`, :qp1 AS `address`, :qp2 AS `status`, :qp3 AS `profile_id`) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)',
            ],
            'query' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, `status`'
                    . ' FROM (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name` = :qp0 LIMIT 1) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `status`=EXCLUDED.`status`',
            ],
            'query with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, `status`'
                    . ' FROM (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name` = :qp0 LIMIT 1) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1',
            ],
            'query without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer`'
                    . ' WHERE `name` = :qp0 LIMIT 1',
            ],
            'values and expressions' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, `ts`) SELECT `email`, `ts`'
                    . ' FROM (SELECT :qp0 AS `email`, CURRENT_TIMESTAMP AS `ts`) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `ts`=EXCLUDED.`ts`',
            ],
            'values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, `ts`) SELECT `email`, `ts`'
                    . ' FROM (SELECT :qp0 AS `email`, CURRENT_TIMESTAMP AS `ts`) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `orders`=T_upsert.orders + 1',
            ],
            'values and expressions without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)',
            ],
            'query, values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[ts]]) SELECT `email`, [[ts]]'
                    . ' FROM (SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]) AS EXCLUDED'
                    . ' ON DUPLICATE KEY UPDATE `ts`=:qp1, `orders`=T_upsert.orders + 1',
            ],
            'query, values and expressions without update part' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]',
            ],
            'no columns to update' => [
                3 => 'INSERT IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0)',
            ],
            'no columns to update with unique' => [
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`) VALUES (:qp0)',
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

    public static function upsertReturning(): array
    {
        $upsert = self::upsert();
        $quoter = self::getDb()->getQuoter();

        foreach ($upsert as &$data) {
            array_splice($data, 3, 0, [['id']]);
            $quotedTable = $quoter->quoteTableName($data[0]);
            $data[4] .= ", `id`=LAST_INSERT_ID($quotedTable.`id`);SELECT LAST_INSERT_ID() AS `id`";
        }

        $upsert['regular values without update part'][4] = 'INSERT INTO `T_upsert`'
            . ' (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)'
            . ' ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`T_upsert`.`id`);SELECT LAST_INSERT_ID() AS `id`';

        $upsert['values and expressions without update part'][4] = 'INSERT INTO `T_upsert` (`email`, `ts`)'
            . ' VALUES (:qp0, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`T_upsert`.`id`);'
            . 'SELECT LAST_INSERT_ID() AS `id`';

        $upsert['query without update part'][4] = 'INSERT INTO `T_upsert` (`email`, `status`)'
            . ' SELECT `email`, 2 AS `status` FROM `customer` WHERE `name` = :qp0 LIMIT 1'
            . ' ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`T_upsert`.`id`);SELECT LAST_INSERT_ID() AS `id`';

        $upsert['query, values and expressions without update part'][4] = 'INSERT INTO `T_upsert` (`email`, [[ts]])'
            . ' SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]'
            . ' ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`T_upsert`.`id`);SELECT LAST_INSERT_ID() AS `id`';

        $upsert['no columns to update'][3] = ['a'];
        $upsert['no columns to update'][4] = 'INSERT IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0);SELECT :qp1 AS `a`';
        $upsert['no columns to update'][5][':qp1'] = 1;

        $upsert['no columns to update with unique'][4] = 'INSERT INTO `T_upsert` (`email`) VALUES (:qp0)'
            . ' ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`T_upsert`.`id`);SELECT LAST_INSERT_ID() AS `id`';

        $upsert['no unique columns in table - simple insert'][4] = 'INSERT INTO {{%animal}} (`type`) VALUES (:qp0);'
            . 'SELECT LAST_INSERT_ID() AS `id`';

        return [
            ...$upsert,
            'composite primary key' => [
                'notauto_pk',
                ['id_1' => 1, 'id_2' => 2.5, 'type' => 'Test'],
                true,
                ['id_1', 'id_2'],
                'INSERT INTO `notauto_pk` (`id_1`, `id_2`, `type`) SELECT `id_1`, `id_2`, `type`'
                . ' FROM (SELECT :qp0 AS `id_1`, :qp1 AS `id_2`, :qp2 AS `type`) AS EXCLUDED'
                . ' ON DUPLICATE KEY UPDATE `type`=EXCLUDED.`type`;SELECT :qp3 AS `id_1`, :qp4 AS `id_2`',
                [':qp0' => 1, ':qp1' => 2.5, ':qp2' => 'Test', ':qp3' => 1, ':qp4' => 2.5],
            ],
            'no return columns' => [
                'type',
                ['int_col' => 3, 'char_col' => 'a', 'float_col' => 1.2, 'bool_col' => true],
                true,
                [],
                'INSERT INTO `type` (`int_col`, `char_col`, `float_col`, `bool_col`) VALUES (:qp0, :qp1, :qp2, :qp3)',
                [':qp0' => 3, ':qp1' => 'a', ':qp2' => 1.2, ':qp3' => true],
            ],
            'return all columns' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'test address', 'status' => 1, 'profile_id' => 1],
                true,
                null,
                'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`)'
                . ' SELECT `email`, `address`, `status`, `profile_id`'
                . ' FROM (SELECT :qp0 AS `email`, :qp1 AS `address`, :qp2 AS `status`, :qp3 AS `profile_id`) AS EXCLUDED'
                . ' ON DUPLICATE KEY UPDATE `address`=EXCLUDED.`address`, `status`=EXCLUDED.`status`,'
                . ' `profile_id`=EXCLUDED.`profile_id`, `id`=LAST_INSERT_ID(`T_upsert`.`id`);'
                . 'SELECT `id`, `ts`, `email`, `recovery_email`, `address`, `status`, `orders`, `profile_id`'
                . ' FROM `T_upsert` WHERE `id` = LAST_INSERT_ID()',
                [':qp0' => 'test@example.com', ':qp1' => 'test address', ':qp2' => 1, ':qp3' => 1],
            ],
            'no primary key' => [
                'type',
                ['int_col' => 3, 'char_col' => 'a', 'float_col' => 1.2, 'bool_col' => true],
                true,
                ['int_col', 'char_col', 'char_col2', 'char_col3'],
                'INSERT INTO `type` (`int_col`, `char_col`, `float_col`, `bool_col`) VALUES (:qp0, :qp1, :qp2, :qp3);'
                . 'SELECT :qp4 AS `int_col`, :qp5 AS `char_col`, :qp6 AS `char_col2`, :qp7 AS `char_col3`',
                [':qp0' => 3, ':qp1' => 'a', ':qp2' => 1.2, ':qp3' => true, ':qp4' => 3, ':qp5' => 'a', ':qp6' => 'something', ':qp7' => null],
            ],
            'no primary key but unique' => [
                'without_pk',
                ['email' => 'test@example.com', 'name' => 'John Doe'],
                true,
                null,
                'INSERT INTO `without_pk` (`email`, `name`) SELECT `email`, `name`'
                . ' FROM (SELECT :qp0 AS `email`, :qp1 AS `name`) AS EXCLUDED'
                . ' ON DUPLICATE KEY UPDATE `name`=EXCLUDED.`name`;'
                . 'SELECT `email`, `name`, `address`, `status` FROM `without_pk` WHERE `email` = :qp2',
                [':qp0' => 'test@example.com', ':qp1' => 'John Doe', ':qp2' => 'test@example.com'],
            ],
        ];
    }

    public static function buildColumnDefinition(): array
    {
        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'int PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UPK][0] = 'int UNSIGNED PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::BIGPK][0] = 'bigint PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UBIGPK][0] = 'bigint UNSIGNED PRIMARY KEY AUTO_INCREMENT';
        $values[PseudoType::UUID_PK][0] = "binary(16) PRIMARY KEY DEFAULT (unhex(replace(uuid(),'-','')))";
        $values[PseudoType::UUID_PK_SEQ][0] = "binary(16) PRIMARY KEY DEFAULT (unhex(replace(uuid(),'-','')))";
        $values['primaryKey()'][0] = 'int PRIMARY KEY AUTO_INCREMENT';
        $values['primaryKey(false)'][0] = 'int PRIMARY KEY';
        $values['smallPrimaryKey()'][0] = 'smallint PRIMARY KEY AUTO_INCREMENT';
        $values['bigPrimaryKey()'][0] = 'bigint PRIMARY KEY AUTO_INCREMENT';
        $values['uuidPrimaryKey()'][0] = "binary(16) PRIMARY KEY DEFAULT (unhex(replace(uuid(),'-','')))";
        $values['uuidPrimaryKey(false)'][0] = 'binary(16) PRIMARY KEY';
        $values['boolean()'][0] = 'bit(1)';
        $values['boolean(100)'][0] = 'bit(1)';
        $values['integer()'][0] = 'int';
        $values['integer(8)'][0] = 'int(8)';
        $values['double(10)'][0] = 'double(10,0)';
        $values['money()'][0] = 'decimal(19,4)';
        $values['money(10)'][0] = 'decimal(10,4)';
        $values['money(10,2)'][0] = 'decimal(10,2)';
        $values['money(null)'][0] = 'decimal';
        $values['binary()'][0] = 'blob';
        $values['binary(1000)'][0] = 'blob(1000)';
        $values['uuid()'][0] = 'binary(16)';
        $values['datetimeWithTimezone()'][0] = 'datetime(0)';
        $values['datetimeWithTimezone(6)'][0] = 'datetime(6)';
        $values['datetimeWithTimezone(null)'][0] = 'datetime';
        $values['timeWithTimezone()'][0] = 'time(0)';
        $values['timeWithTimezone(6)'][0] = 'time(6)';
        $values['timeWithTimezone(null)'][0] = 'time';
        $values["check('value > 5')"][0] = 'int CHECK (`check_col` > 5)';
        $values["check('')"][0] = 'int';
        $values['check(null)'][0] = 'int';
        $values['defaultValue($expression)'][0] = 'int DEFAULT (1 + 2)';
        $values['defaultValue($emptyExpression)'][0] = 'int';
        $values["characterSet('utf8mb4')"] = [
            'varchar(255) CHARACTER SET utf8mb4',
            ColumnBuilder::string()->characterSet('utf8mb4'),
        ];
        $values["characterSet('')"] = ['varchar(255)', ColumnBuilder::string()->characterSet('')];
        $values['characterSet(null)'] = ['varchar(255)', ColumnBuilder::string()->characterSet(null)];
        $values["collation('collation_name')"] = [
            'varchar(255) COLLATE utf8mb4_unicode_ci',
            ColumnBuilder::string()->collation('utf8mb4_unicode_ci'),
        ];
        $values["comment('comment')"][0] = "varchar(255) COMMENT 'comment'";
        $values["comment('')"][0] = "varchar(255) COMMENT ''";
        $values['integer()->primaryKey()'][0] = 'int PRIMARY KEY';
        $values["integer()->defaultValue('')"][0] = 'int DEFAULT NULL';
        $values['unsigned()'][0] = 'int UNSIGNED';
        $values['integer(8)->scale(2)'][0] = 'int(8)';
        $values['reference($reference)'][0] = 'int REFERENCES `ref_table` (`id`) ON DELETE SET NULL ON UPDATE CASCADE';
        $values['reference($referenceWithSchema)'][0] = 'int REFERENCES `ref_schema`.`ref_table` (`id`) ON DELETE SET NULL ON UPDATE CASCADE';

        $values[] = ["enum('a','b','c')", ColumnBuilder::string()->dbType("enum('a','b','c')")];

        $db = self::getDb();
        $serverVersion = $db->getServerInfo()->getVersion();
        $db->close();

        if (!str_contains($serverVersion, 'MariaDB')
            && version_compare($serverVersion, '8', '<')
        ) {
            $values[PseudoType::UUID_PK][0] = 'binary(16) PRIMARY KEY';
            $values[PseudoType::UUID_PK_SEQ][0] = 'binary(16) PRIMARY KEY';
            $values['uuidPrimaryKey()'][0] = 'binary(16) PRIMARY KEY';
            $values['defaultValue($expression)'] = ['int DEFAULT 3', ColumnBuilder::integer()->defaultValue(3)];
            $values['timestamp(6)'] = ['timestamp(6) DEFAULT CURRENT_TIMESTAMP(6)', ColumnBuilder::timestamp(6)->defaultValue(new Expression('CURRENT_TIMESTAMP(6)'))];
            $values['timestamp(null)'] = ['timestamp DEFAULT CURRENT_TIMESTAMP', ColumnBuilder::timestamp(null)->defaultValue(new Expression('CURRENT_TIMESTAMP'))];
        }

        return $values;
    }

    public static function prepareValue(): array
    {
        $values = parent::prepareValue();

        $values['array'][0] = '\'[\\"a\\",\\"b\\",\\"c\\"]\'';
        $values['json'][0] = '\'{\\"a\\":1,\\"b\\":2}\'';
        $values['Iterator'][0] = '\'[\\"a\\",\\"b\\",\\"c\\"]\'';
        $values['Traversable'][0] = '\'{\\"a\\":1,\\"b\\":2}\'';
        $values['JsonSerializable'][0] = '\'{\\"a\\":1,\\"b\\":2}\'';

        return $values;
    }

    public static function multiOperandFunctionClasses(): array
    {
        return [
            ...parent::multiOperandFunctionClasses(),
            ArrayMerge::class => [ArrayMerge::class],
        ];
    }

    public static function multiOperandFunctionBuilder(): array
    {
        $data = parent::multiOperandFunctionBuilder();

        $stringParam = new Param('[3,4,5]', DataType::STRING);

        $data['Longest with 2 operands'][2] = "(SELECT 'short' AS value UNION SELECT :qp0 AS value ORDER BY LENGTH(value) DESC LIMIT 1)";
        $data['Longest with 3 operands'][2] = "(SELECT 'short' AS value UNION SELECT (SELECT 'longest') AS value UNION SELECT :qp0 AS value ORDER BY LENGTH(value) DESC LIMIT 1)";
        $data['Shortest with 2 operands'][2] = "(SELECT 'short' AS value UNION SELECT :qp0 AS value ORDER BY LENGTH(value) ASC LIMIT 1)";
        $data['Shortest with 3 operands'][2] = "(SELECT 'short' AS value UNION SELECT (SELECT 'longest') AS value UNION SELECT :qp0 AS value ORDER BY LENGTH(value) ASC LIMIT 1)";

        $db = self::getDb();
        $serverVersion = $db->getServerInfo()->getVersion();
        $db->close();

        $isMariadb = str_contains($serverVersion, 'MariaDB');

        if (
            $isMariadb && version_compare($serverVersion, '10.6', '<')
            || !$isMariadb && version_compare($serverVersion, '8.0.0', '<')
        ) {
            // MariaDB < 10.6 and MySQL < 8 does not support JSON_TABLE() function.
            return $data;
        }

        $data['ArrayMerge with 1 operand'] = [
            ArrayMerge::class,
            ["'[1,2,3]'"],
            "('[1,2,3]')",
            [1, 2, 3],
        ];
        $data['ArrayMerge with 2 operands'] = [
            ArrayMerge::class,
            ["'[1,2,3]'", $stringParam],
            '(SELECT JSON_ARRAYAGG(value) AS value FROM ('
            . "SELECT value FROM JSON_TABLE('[1,2,3]', '$[*]' COLUMNS(value json PATH '$')) AS t"
            . " UNION SELECT value FROM JSON_TABLE(:qp0, '$[*]' COLUMNS(value json PATH '$')) AS t) AS t)",
            [1, 2, 3, 4, 5],
            [':qp0' => $stringParam],
        ];

        if ($isMariadb) {
            // MySQL does not support query parameters in JSON_TABLE() function.
            $data['ArrayMerge with 4 operands'] = [
                ArrayMerge::class,
                ["'[1,2,3]'", [5, 6, 7], $stringParam, self::getDb()->select(new ArrayExpression([9, 10]))],
                '(SELECT JSON_ARRAYAGG(value) AS value FROM ('
                . "SELECT value FROM JSON_TABLE('[1,2,3]', '$[*]' COLUMNS(value json PATH '$')) AS t"
                . " UNION SELECT value FROM JSON_TABLE(:qp0, '$[*]' COLUMNS(value json PATH '$')) AS t"
                . " UNION SELECT value FROM JSON_TABLE(:qp1, '$[*]' COLUMNS(value json PATH '$')) AS t"
                . " UNION SELECT value FROM JSON_TABLE((SELECT :qp2), '$[*]' COLUMNS(value json PATH '$')) AS t"
                . ') AS t)',
                [1, 2, 3, 4, 5, 6, 7, 9, 10],
                [
                    ':qp0' => new Param('[5,6,7]', DataType::STRING),
                    ':qp1' => $stringParam,
                    ':qp2' => new Param('[9,10]', DataType::STRING),
                ],
            ];
        }

        return $data;
    }

    public static function dateTimeValue(): iterable
    {
        foreach (parent::dateTimeValue() as $key => $value) {
            // MySQL cannot store date in float column
            if ($key === 'Float') {
                continue;
            }
            yield $key => $value;
        }
    }
}
