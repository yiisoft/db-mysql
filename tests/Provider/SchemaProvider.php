<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'bigint(20) unsigned',
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
                        'dbType' => 'tinyint(3)',
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
                        'dbType' => 'smallint(1)',
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
                        'dbType' => 'char(100)',
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
                        'dbType' => 'varchar(100)',
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
                        'dbType' => "enum('a','B','c,D')",
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
                        'dbType' => 'double(4,3)',
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
                        'dbType' => 'decimal(5,2)',
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
                        'dbType' => 'bit(1)',
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
                        'dbType' => 'tinyint(1)',
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
                        'dbType' => 'bit(8)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'varchar(255)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'varchar(255)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'int(11)',
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
                        'dbType' => 'bit(1)',
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
                        'dbType' => 'bit(1)',
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
                        'dbType' => 'bit(32)',
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
                        'dbType' => 'bit(32)',
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
                        'dbType' => 'bit(64)',
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
                        'dbType' => 'bit(64)',
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
