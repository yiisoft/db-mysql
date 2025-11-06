<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function char(?int $size = 1): StringColumn
    {
        return new StringColumn(ColumnType::CHAR, size: $size);
    }

    public static function string(?int $size = 255): StringColumn
    {
        return new StringColumn(ColumnType::STRING, size: $size);
    }

    public static function text(?int $size = null): StringColumn
    {
        return new StringColumn(ColumnType::TEXT, size: $size);
    }

    public static function timestamp(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIMESTAMP, size: $size);
    }

    public static function datetime(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIME, size: $size);
    }

    public static function datetimeWithTimezone(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIMETZ, size: $size);
    }

    public static function time(?int $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIME, size: $size);
    }

    public static function timeWithTimezone(?int $size = 0): DateTimeColumn
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
