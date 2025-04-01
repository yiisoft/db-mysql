<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    use TestTrait;

    public static function columns(): array
    {
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
                    'time' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        defaultValue: '2002-01-01 00:00:00',
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
                    'ts_default' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        defaultValue: new Expression('CURRENT_TIMESTAMP'),
                    ),
                    'bit_col' => new BitColumn(
                        dbType: 'bit',
                        notNull: true,
                        size: 8,
                        defaultValue: 130, // b'10000010'
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
                        autoIncrement: true,
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
        $constraints['2: primary key'][2]->name(null);
        $constraints['2: check'][2] = false;
        $constraints['3: check'][2] = false;
        $constraints['4: check'][2] = false;

        return $constraints;
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
