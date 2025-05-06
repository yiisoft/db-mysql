<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bit', ColumnType::BIT, BitColumn::class],
            ['tinyint', ColumnType::TINYINT, IntegerColumn::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumn::class],
            ['mediumint', ColumnType::INTEGER, IntegerColumn::class],
            ['int', ColumnType::INTEGER, IntegerColumn::class],
            ['integer', ColumnType::INTEGER, IntegerColumn::class],
            ['bigint', ColumnType::BIGINT, IntegerColumn::class],
            ['float', ColumnType::FLOAT, DoubleColumn::class],
            ['real', ColumnType::FLOAT, DoubleColumn::class],
            ['double', ColumnType::DOUBLE, DoubleColumn::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumn::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumn::class],
            ['char', ColumnType::CHAR, StringColumn::class],
            ['varchar', ColumnType::STRING, StringColumn::class],
            ['string', ColumnType::STRING, StringColumn::class],
            ['enum', ColumnType::STRING, StringColumn::class],
            ['tinytext', ColumnType::TEXT, StringColumn::class],
            ['mediumtext', ColumnType::TEXT, StringColumn::class],
            ['longtext', ColumnType::TEXT, StringColumn::class],
            ['text', ColumnType::TEXT, StringColumn::class],
            ['varbinary', ColumnType::BINARY, BinaryColumn::class],
            ['blob', ColumnType::BINARY, BinaryColumn::class],
            ['longblob', ColumnType::BINARY, BinaryColumn::class],
            ['year', ColumnType::SMALLINT, IntegerColumn::class],
            ['timestamp', ColumnType::TIMESTAMP, DateTimeColumn::class],
            ['datetime', ColumnType::DATETIME, DateTimeColumn::class],
            ['time', ColumnType::TIME, DateTimeColumn::class],
            ['date', ColumnType::DATE, DateTimeColumn::class],
            ['json', ColumnType::JSON, JsonColumn::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions[] = ['bit(1)', new BooleanColumn(dbType: 'bit', size: 1)];

        return $definitions;
    }

    public static function defaultValueRaw(): array
    {
        return [
            // type, default value, expected value
            [ColumnType::STRING, null, null],
            [ColumnType::STRING, '', ''],
            [ColumnType::INTEGER, '-1', -1],
            [ColumnType::DOUBLE, '0.0', 0.0],
            [ColumnType::DOUBLE, '-1.1', -1.1],
            [ColumnType::BOOLEAN, "b'1'", true],
            [ColumnType::BOOLEAN, "b'0'", false],
            [ColumnType::BIT, "b'1011'", 0b1011],
            [ColumnType::STRING, "'str''ing'", "str'ing"],
            [ColumnType::TIMESTAMP, 'CURRENT_TIMESTAMP', new Expression('CURRENT_TIMESTAMP')],
            [ColumnType::TIMESTAMP, 'current_timestamp(3)', new Expression('CURRENT_TIMESTAMP(3)')],
            [ColumnType::INTEGER, '(1 + 2)', new Expression('(1 + 2)')],
        ];
    }
}
