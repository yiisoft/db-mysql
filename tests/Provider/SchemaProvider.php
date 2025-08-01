<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use DateTimeImmutable;
use DateTimeZone;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\DateTimeColumn;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\Assert;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    use TestTrait;

    public static function columns(): array
    {
        $db = self::getDb();
        $dbTimezone = self::getDb()->getServerInfo()->getTimezone();
        $db->close();

        return [
            [
                [
                    'int_col' => new IntegerColumn(
                        dbType: 'int',
                        notNull: true,
                        size: 11,
                    ),
                    'int_col2' => new IntegerColumn(
                        dbType: 'int',
                        size: 11,
                        defaultValue: 1,
                    ),
                    'bigunsigned_col' => new BigIntColumn(
                        dbType: 'bigint',
                        size: 20,
                        unsigned: true,
                        defaultValue: '12345678901234567890',
                    ),
                    'tinyint_col' => new IntegerColumn(
                        ColumnType::TINYINT,
                        dbType: 'tinyint',
                        size: 3,
                        defaultValue: 1,
                    ),
                    'smallint_col' => new IntegerColumn(
                        ColumnType::SMALLINT,
                        dbType: 'smallint',
                        size: 1,
                        defaultValue: 1,
                    ),
                    'mediumint_col' => new IntegerColumn(
                        dbType: 'mediumint',
                        size: 9,
                    ),
                    'char_col' => new StringColumn(
                        ColumnType::CHAR,
                        dbType: 'char',
                        notNull: true,
                        size: 100,
                    ),
                    'char_col2' => new StringColumn(
                        dbType: 'varchar',
                        size: 100,
                        defaultValue: 'something',
                    ),
                    'char_col3' => new StringColumn(
                        ColumnType::TEXT,
                        dbType: 'text',
                    ),
                    'enum_col' => new StringColumn(
                        dbType: 'enum',
                        enumValues: ['a', 'B', 'c,D'],
                    ),
                    'float_col' => new DoubleColumn(
                        dbType: 'double',
                        notNull: true,
                        size: 4,
                        scale: 3,
                    ),
                    'float_col2' => new DoubleColumn(
                        dbType: 'double',
                        defaultValue: 1.23,
                    ),
                    'blob_col' => new BinaryColumn(
                        dbType: 'blob',
                    ),
                    'numeric_col' => new DoubleColumn(
                        ColumnType::DECIMAL,
                        dbType: 'decimal',
                        size: 5,
                        scale: 2,
                        defaultValue: 33.22,
                    ),
                    'timestamp_col' => new DateTimeColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        defaultValue: new DateTimeImmutable('2002-01-01 00:00:00', new DateTimeZone('UTC')),
                        shouldConvertTimezone: true,
                        dbTimezone: $dbTimezone,
                    ),
                    'timestamp_default' => new DateTimeColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        defaultValue: new Expression('CURRENT_TIMESTAMP'),
                        dbTimezone: $dbTimezone,
                    ),
                    'bool_col' => new BooleanColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 1,
                    ),
                    'tiny_col' => new IntegerColumn(
                        ColumnType::TINYINT,
                        dbType: 'tinyint',
                        size: 1,
                        defaultValue: 2,
                    ),
                    'bit_col' => new BitColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 8,
                        defaultValue: 130, // b'10000010'
                    ),
                    'tinyblob_col' => new BinaryColumn(
                        dbType: 'tinyblob',
                    ),
                    'tinytext_col' => new StringColumn(
                        ColumnType::TEXT,
                        dbType: 'tinytext',
                    ),
                    'mediumblob_col' => new BinaryColumn(
                        dbType: 'mediumblob',
                    ),
                    'mediumtext_col' => new StringColumn(
                        ColumnType::TEXT,
                        dbType: 'mediumtext',
                    ),
                    'json_col' => new JsonColumn(
                        dbType: 'json',
                    ),
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => new IntegerColumn(
                        dbType: 'int',
                        primaryKey: true,
                        notNull: true,
                        autoIncrement: true,
                        size: 11,
                    ),
                    'type' => new StringColumn(
                        dbType: 'varchar',
                        notNull: true,
                        size: 255,
                    ),
                ],
                'animal',
            ],
            [
                [
                    'C_id' => new IntegerColumn(
                        dbType: 'int',
                        primaryKey: true,
                        notNull: true,
                        size: 11,
                    ),
                    'C_not_null' => new IntegerColumn(
                        dbType: 'int',
                        notNull: true,
                        size: 11,
                    ),
                    'C_check' => new StringColumn(
                        dbType: 'varchar',
                        size: 255,
                    ),
                    'C_unique' => new IntegerColumn(
                        dbType: 'int',
                        notNull: true,
                        size: 11,
                        unique: true,
                    ),
                    'C_default' => new IntegerColumn(
                        dbType: 'int',
                        notNull: true,
                        size: 11,
                        defaultValue: 0,
                    ),
                ],
                'T_constraints_1',
            ],
        ];
    }

    public static function columnsTypeBit(): array
    {
        return [
            [
                [
                    'bit_col_1' => new BooleanColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 1,
                        defaultValue: false,
                    ),
                    'bit_col_2' => new BooleanColumn(
                        dbType: 'bit',
                        size: 1,
                        defaultValue: true,
                    ),
                    'bit_col_3' => new BitColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 32,
                    ),
                    'bit_col_4' => new BitColumn(
                        dbType: 'bit',
                        size: 32,
                        defaultValue: 130,
                    ),
                    'bit_col_5' => new BitColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 64,
                    ),
                    'bit_col_6' => new BitColumn(
                        dbType: 'bit',
                        size: 64,
                        defaultValue: 130,
                    ),
                ],
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2] = false;
        Assert::setPropertyValue($constraints['2: primary key'][2], 'name', 'PRIMARY');
        Assert::setPropertyValue($constraints['2: unique'][2][0], 'name', 'PRIMARY');
        Assert::setPropertyValue($constraints['2: index'][2][0], 'name', 'PRIMARY');
        $constraints['2: check'][2] = false;
        $constraints['3: check'][2] = false;
        $constraints['4: check'][2] = false;

        return $constraints;
    }

    public static function resultColumns(): array
    {
        $db = self::getDb();
        $dbTimezone = self::getDb()->getServerInfo()->getTimezone();
        $db->close();

        return [
            [null, []],
            [null, ['native_type' => 'NULL']],
            [new IntegerColumn(dbType: 'int', name: 'int_col', notNull: true, size: 11), [
                'native_type' => 'LONG',
                'pdo_type' => 1,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'int_col',
                'len' => 11,
                'precision' => 0,
            ]],
            [new BigIntColumn(dbType: 'bigint', name: 'bigunsigned_col', notNull: false, size: 20, unsigned: true), [
                'native_type' => 'LONGLONG',
                'pdo_type' => 1,
                'flags' => [],
                'table' => 'type',
                'name' => 'bigunsigned_col',
                'len' => 20,
                'precision' => 0,
            ]],
            [new IntegerColumn(ColumnType::TINYINT, dbType: 'tinyint', name: 'tinyint_col', notNull: false, size: 3), [
                'native_type' => 'TINY',
                'pdo_type' => 1,
                'flags' => [],
                'table' => 'type',
                'name' => 'tinyint_col',
                'len' => 3,
                'precision' => 0,
            ]],
            [new IntegerColumn(ColumnType::SMALLINT, dbType: 'smallint', name: 'smallint_col', notNull: false, size: 1), [
                'native_type' => 'SHORT',
                'pdo_type' => 1,
                'flags' => [],
                'table' => 'type',
                'name' => 'smallint_col',
                'len' => 1,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::CHAR, dbType: 'char', name: 'char_col', notNull: true, size: 400), [
                'native_type' => 'STRING',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'char_col',
                'len' => 400,
                'precision' => 0,
            ]],
            [new StringColumn(dbType: 'varchar', name: 'char_col2', notNull: false, size: 400), [
                'native_type' => 'VAR_STRING',
                'pdo_type' => 2,
                'flags' => [],
                'table' => 'type',
                'name' => 'char_col2',
                'len' => 400,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::TEXT, dbType: 'text', name: 'char_col3', notNull: false, size: 262140), [
                'native_type' => 'BLOB',
                'pdo_type' => 2,
                'flags' => ['blob'],
                'table' => 'type',
                'name' => 'char_col3',
                'len' => 262140,
                'precision' => 0,
            ]],
            [new DoubleColumn(dbType: 'double', name: 'float_col', notNull: true, size: 4, scale: 3), [
                'native_type' => 'DOUBLE',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'float_col',
                'len' => 4,
                'precision' => 3,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'decimal', name: 'numeric_col', notNull: false, size: 5, scale: 2), [
                'native_type' => 'NEWDECIMAL',
                'pdo_type' => 2,
                'flags' => [],
                'table' => 'type',
                'name' => 'numeric_col',
                'len' => 7,
                'precision' => 2,
            ]],
            [new DateTimeColumn(ColumnType::TIMESTAMP, dbType: 'timestamp', name: 'time', notNull: true, size: 0, dbTimezone: $dbTimezone), [
                'native_type' => 'TIMESTAMP',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'time',
                'len' => 19,
                'precision' => 0,
            ]],
            [new BooleanColumn(dbType: 'bit', name: 'bool_col', notNull: true, size: 1), [
                'native_type' => 'BIT',
                'pdo_type' => 1,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'bool_col',
                'len' => 1,
                'precision' => 0,
            ]],
            [new BitColumn(dbType: 'bit', name: 'bit_col', notNull: true, size: 8), [
                'native_type' => 'BIT',
                'pdo_type' => 1,
                'flags' => ['not_null'],
                'table' => 'type',
                'name' => 'bit_col',
                'len' => 8,
                'precision' => 0,
            ]],
            [new IntegerColumn(dbType: 'int', name: '1', size: 1, notNull: true), [
                'native_type' => 'LONG',
                'pdo_type' => 1,
                'flags' => ['not_null'],
                'table' => '',
                'name' => '1',
                'len' => 1,
                'precision' => 0,
            ]],
            [new IntegerColumn(dbType: 'int', name: '1', size: 1, notNull: true), [
                'native_type' => 'LONGLONG',
                'pdo_type' => 1,
                'flags' => ['not_null'],
                'table' => '',
                'name' => '1',
                'len' => 1,
                'precision' => 0,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'decimal', name: '2.5', notNull: true, size: 2, scale: 1), [
                'native_type' => 'NEWDECIMAL',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => '',
                'name' => '2.5',
                'len' => 4,
                'precision' => 1,
            ]],
            [new StringColumn(dbType: 'varchar', name: 'string', notNull: true, size: 24), [
                'native_type' => 'VAR_STRING',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => '',
                'name' => 'string',
                'len' => 24,
                'precision' => 39,
            ]],
            [new DateTimeColumn(dbType: 'datetime', name: 'CURRENT_TIMESTAMP(3)', notNull: true, size: 3), [
                'native_type' => 'DATETIME',
                'pdo_type' => 2,
                'flags' => ['not_null'],
                'table' => '',
                'name' => 'CURRENT_TIMESTAMP(3)',
                'len' => 23,
                'precision' => 3,
            ]],
        ];
    }

    public static function tableSchemaWithDbSchemes(): array
    {
        return [
            ['animal', 'animal',],
            ['`other`.`animal2`', 'animal2', 'other',],
            ['other.`animal2`', 'animal2', 'other',],
            ['other.animal2', 'animal2', 'other',],
            ['catalog.other.animal2', 'animal2', 'other'],
        ];
    }
}
