<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
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
            ['bit', 'bit', BitColumnSchema::class],
            ['tinyint', 'tinyint', IntegerColumnSchema::class],
            ['smallint', 'smallint', IntegerColumnSchema::class],
            ['mediumint', 'integer', IntegerColumnSchema::class],
            ['int', 'integer', IntegerColumnSchema::class],
            ['integer', 'integer', IntegerColumnSchema::class],
            ['bigint', 'bigint', IntegerColumnSchema::class],
            ['float', 'float', DoubleColumnSchema::class],
            ['real', 'float', DoubleColumnSchema::class],
            ['double', 'double', DoubleColumnSchema::class],
            ['decimal', 'decimal', DoubleColumnSchema::class],
            ['numeric', 'decimal', DoubleColumnSchema::class],
            ['char', 'char', StringColumnSchema::class],
            ['varchar', 'string', StringColumnSchema::class],
            ['string', 'string', StringColumnSchema::class],
            ['enum', 'string', StringColumnSchema::class],
            ['tinytext', 'text', StringColumnSchema::class],
            ['mediumtext', 'text', StringColumnSchema::class],
            ['longtext', 'text', StringColumnSchema::class],
            ['text', 'text', StringColumnSchema::class],
            ['varbinary', 'binary', BinaryColumnSchema::class],
            ['blob', 'binary', BinaryColumnSchema::class],
            ['longblob', 'binary', BinaryColumnSchema::class],
            ['year', 'date', StringColumnSchema::class],
            ['date', 'date', StringColumnSchema::class],
            ['time', 'time', StringColumnSchema::class],
            ['datetime', 'datetime', StringColumnSchema::class],
            ['timestamp', 'timestamp', StringColumnSchema::class],
            ['json', 'json', JsonColumnSchema::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions[] = ['bit(1)', 'boolean', BooleanColumnSchema::class, ['getSize' => 1]];

        return $definitions;
    }
}
