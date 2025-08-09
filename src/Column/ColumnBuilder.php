<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function char(int|null $size = 1): StringColumn
    {
        return new StringColumn(ColumnType::CHAR, size: $size);
    }

    public static function string(int|null $size = 255): StringColumn
    {
        return new StringColumn(ColumnType::STRING, size: $size);
    }

    public static function text(int|null $size = null): StringColumn
    {
        return new StringColumn(ColumnType::TEXT, size: $size);
    }

    public static function timestamp(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIMESTAMP, size: $size);
    }

    public static function datetime(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIME, size: $size);
    }

    public static function datetimeWithTimezone(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIMETZ, size: $size);
    }

    public static function time(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIME, size: $size);
    }

    public static function timeWithTimezone(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIMETZ, size: $size);
    }

    public static function date(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATE);
    }

    public static function uuid(): StringColumn
    {
        return new StringColumn(ColumnType::UUID);
    }
}
