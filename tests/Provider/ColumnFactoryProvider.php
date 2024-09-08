<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BitColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bit', ColumnType::BIT, BitColumnSchema::class],
            ['tinyint', ColumnType::TINYINT, IntegerColumnSchema::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['mediumint', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['int', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['integer', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['bigint', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['float', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['real', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['double', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['char', ColumnType::CHAR, StringColumnSchema::class],
            ['varchar', ColumnType::STRING, StringColumnSchema::class],
            ['string', ColumnType::STRING, StringColumnSchema::class],
            ['enum', ColumnType::STRING, StringColumnSchema::class],
            ['tinytext', ColumnType::TEXT, StringColumnSchema::class],
            ['mediumtext', ColumnType::TEXT, StringColumnSchema::class],
            ['longtext', ColumnType::TEXT, StringColumnSchema::class],
            ['text', ColumnType::TEXT, StringColumnSchema::class],
            ['varbinary', ColumnType::BINARY, BinaryColumnSchema::class],
            ['blob', ColumnType::BINARY, BinaryColumnSchema::class],
            ['longblob', ColumnType::BINARY, BinaryColumnSchema::class],
            ['year', ColumnType::DATE, StringColumnSchema::class],
            ['date', ColumnType::DATE, StringColumnSchema::class],
            ['time', ColumnType::TIME, StringColumnSchema::class],
            ['datetime', ColumnType::DATETIME, StringColumnSchema::class],
            ['timestamp', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['json', ColumnType::JSON, JsonColumnSchema::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions[] = ['bit(1)', ColumnType::BOOLEAN, BooleanColumnSchema::class, ['getSize' => 1]];

        return $definitions;
    }
}
