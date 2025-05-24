<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

use function array_replace;

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
                3 => 'INSERT IGNORE INTO `T_upsert` (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)',
            ],
            'query, values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]] ' .
                    'ON DUPLICATE KEY UPDATE `ts`=:qp1, `orders`=T_upsert.orders + 1',
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

    public static function upsertWithReturning(): array
    {
        $upsert = self::upsert();

        foreach ($upsert as &$data) {
            array_splice($data, 3, 0, [['id']]);
            $data[4] .= ';SELECT LAST_INSERT_ID() `id`';
        }

        $upsert['no columns to update'][3] = ['a'];
        $upsert['no columns to update'][4] = 'INSERT IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0);SELECT :qp1 `a`';
        $upsert['no columns to update'][5][':qp1'] = 1;

        return [
            ...$upsert,
            'composite primary key' => [
                'notauto_pk',
                ['id_1' => 1, 'id_2' => 2.5, 'type' => 'Test'],
                true,
                ['id_1', 'id_2'],
                'INSERT INTO `notauto_pk` (`id_1`, `id_2`, `type`) VALUES (:qp0, :qp1, :qp2)'
                . ' ON DUPLICATE KEY UPDATE `type`=VALUES(`type`);SELECT :qp3 `id_1`, :qp4 `id_2`',
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
                'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)'
                . ' ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`);'
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
                . 'SELECT :qp4 `int_col`, :qp5 `char_col`, :qp6 `char_col2`, :qp7 `char_col3`',
                [':qp0' => 3, ':qp1' => 'a', ':qp2' => 1.2, ':qp3' => true, ':qp4' => 3, ':qp5' => 'a', ':qp6' => 'something', ':qp7' => null],
            ],
            'no primary key but unique' => [
                'without_pk',
                ['email' => 'test@example.com', 'name' => 'John Doe'],
                true,
                null,
                'INSERT INTO `without_pk` (`email`, `name`) VALUES (:qp0, :qp1)'
                . ' ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);'
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
        $values["comment('comment')"][0] = "varchar(255) COMMENT 'comment'";
        $values["comment('')"][0] = "varchar(255) COMMENT ''";
        $values['integer()->primaryKey()'][0] = 'int PRIMARY KEY';
        $values["integer()->defaultValue('')"][0] = 'int DEFAULT NULL';
        $values['unsigned()'][0] = 'int UNSIGNED';
        $values['integer(8)->scale(2)'][0] = 'int(8)';
        $values['reference($reference)'][0] = 'int REFERENCES `ref_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';
        $values['reference($referenceWithSchema)'][0] = 'int REFERENCES `ref_schema`.`ref_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';

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
}
