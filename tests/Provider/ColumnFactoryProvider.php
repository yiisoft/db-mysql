<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\DateTimeColumn;
use Yiisoft\Db\Mysql\Column\StringColumn;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;

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
            ['decimal', ColumnType::DECIMAL, StringColumn::class],
            ['numeric', ColumnType::DECIMAL, StringColumn::class],
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

        $definitions[''][1] = new StringColumn(dbType: '');
        $definitions['text'][1] = new StringColumn(ColumnType::TEXT, dbType: 'text');
        $definitions['text NOT NULL'][1] = new StringColumn(ColumnType::TEXT, dbType: 'text', notNull: true);
        $definitions['char(1)'][1] = new StringColumn(ColumnType::CHAR, dbType: 'char', size: 1);
        $definitions['string(126)[][]'][1] = new ArrayColumn(size: 126, dimension: 2, column: new StringColumn(size: 126));
        $definitions['decimal(10,2)'][1] = new StringColumn(ColumnType::DECIMAL, dbType: 'decimal', scale: 2, size: 10);
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

    public static function pseudoTypes(): array
    {
        $pseudoTypes = parent::pseudoTypes();
        $pseudoTypes['uuid_pk'][1] = new StringColumn(ColumnType::UUID, primaryKey: true, autoIncrement: true);
        $pseudoTypes['uuid_pk_seq'][1] = new StringColumn(ColumnType::UUID, primaryKey: true, autoIncrement: true);

        return $pseudoTypes;
    }
}
