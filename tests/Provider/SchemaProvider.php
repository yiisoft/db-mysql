<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    use TestTrait;

    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'bigunsigned_col' => [
                        'type' => 'bigint',
                        'dbType' => 'bigint',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 20,
                        'scale' => null,
                        'unsigned' => true,
                        'defaultValue' => '12345678901234567890',
                    ],
                    'tinyint_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'smallint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'char',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => 'something',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'enum_col' => [
                        'type' => 'string',
                        'dbType' => 'enum',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => ['a', 'B', 'c,D'],
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'double',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4,
                        'scale' => 3,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'double',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'blob',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'time' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'tiny_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => 2,
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
                    ],
                    'bit_col' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 8,
                        'scale' => null,
                        'defaultValue' => 130, // b'10000010'
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'animal',
            ],
            [
                [
                    'C_id' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_not_null' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_check' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                        'unique' => false,
                    ],
                    'C_unique' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                        'unique' => true,
                    ],
                    'C_default' => [
                        'type' => 'integer',
                        'dbType' => 'int',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'scale' => null,
                        'defaultValue' => 0,
                        'unique' => false,
                    ],
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
                    'bit_col_1' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => false,
                    ],
                    'bit_col_2' => [
                        'type' => 'boolean',
                        'dbType' => 'bit',
                        'phpType' => 'bool',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => true,
                    ],
                    'bit_col_3' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 32,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bit_col_4' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 32,
                        'scale' => null,
                        'defaultValue' => 130,
                    ],
                    'bit_col_5' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 64,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bit_col_6' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 64,
                        'scale' => null,
                        'defaultValue' => 130,
                    ],
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

    public static function resultColumns(): array
    {
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
            [new StringColumn(ColumnType::TIMESTAMP, dbType: 'timestamp', name: 'time', notNull: true, size: 0), [
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
            [new StringColumn(ColumnType::DATETIME, dbType: 'datetime', name: 'CURRENT_TIMESTAMP(3)', notNull: true, size: 3), [
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
