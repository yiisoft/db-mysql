<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use DateTime;
use DateTimeImmutable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Column\DateTimeColumn;
use Yiisoft\Db\Tests\Support\Stringable;

final class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['datetime'][0] = DateTimeColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 0);
        $values['timestamp6'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 6);
        $values['datetime'][0] = new DateTimeColumn(size: 0);
        $values['datetime6'][0] = new DateTimeColumn(size: 6);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME, size: 0);
        $values['time6'][0] = new DateTimeColumn(ColumnType::TIME, size: 6);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 0);
        $values['timetz6'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 6);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);

        // Remove time zones from the expected values.
        $values['datetimetz'] = [
            new DateTimeColumn(ColumnType::DATETIMETZ, size: 0),
            [
                [null, null],
                [null, ''],
                ['2025-04-19 00:00:00', '2025-04-19'],
                ['2025-04-19 14:11:35', '2025-04-19 14:11:35'],
                ['2025-04-19 14:11:35', '2025-04-19 14:11:35.123456'],
                ['2025-04-19 12:11:35', '2025-04-19 14:11:35 +02:00'],
                ['2025-04-19 12:11:35', '2025-04-19 14:11:35.123456 +02:00'],
                ['2025-04-19 14:11:35', '1745071895'],
                ['2025-04-19 14:11:35', '1745071895.123'],
                ['2025-04-19 14:11:35', 1745071895],
                ['2025-04-19 14:11:35', 1745071895.123],
                ['2025-04-19 12:11:35', new DateTimeImmutable('2025-04-19 14:11:35 +02:00')],
                ['2025-04-19 12:11:35', new DateTime('2025-04-19 14:11:35 +02:00')],
                ['2025-04-19 12:11:35', new Stringable('2025-04-19 14:11:35 +02:00')],
                [$expression = new Expression("'2025-04-19 14:11:35'"), $expression],
            ],
        ];
        $values['datetimetz6'] = [
            new DateTimeColumn(ColumnType::DATETIMETZ, size: 6),
            [
                [null, null],
                [null, ''],
                ['2025-04-19 00:00:00.000000', '2025-04-19'],
                ['2025-04-19 14:11:35.000000', '2025-04-19 14:11:35'],
                ['2025-04-19 14:11:35.123456', '2025-04-19 14:11:35.123456'],
                ['2025-04-19 12:11:35.000000', '2025-04-19 14:11:35 +02:00'],
                ['2025-04-19 12:11:35.123456', '2025-04-19 14:11:35.123456 +02:00'],
                ['2025-04-19 14:11:35.000000', '1745071895'],
                ['2025-04-19 14:11:35.123000', '1745071895.123'],
                ['2025-04-19 14:11:35.000000', 1745071895],
                ['2025-04-19 14:11:35.123000', 1745071895.123],
                ['2025-04-19 12:11:35.123456', new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00')],
                ['2025-04-19 12:11:35.123456', new DateTime('2025-04-19 14:11:35.123456 +02:00')],
                ['2025-04-19 12:11:35.123456', new Stringable('2025-04-19 14:11:35.123456 +02:00')],
                [$expression = new Expression("'2025-04-19 14:11:35.123456 +02:00'"), $expression],
            ],
        ];
        $values['timetz'] = [
            new DateTimeColumn(ColumnType::TIMETZ, size: 0),
            [
                [null, null],
                [null, ''],
                ['00:00:00', '2025-04-19'],
                ['14:11:35', '14:11:35'],
                ['14:11:35', '14:11:35.123456'],
                ['12:11:35', '14:11:35 +02:00'],
                ['12:11:35', '14:11:35.123456 +02:00'],
                ['14:11:35', '2025-04-19 14:11:35'],
                ['14:11:35', '2025-04-19 14:11:35.123456'],
                ['12:11:35', '2025-04-19 14:11:35 +02:00'],
                ['12:11:35', '2025-04-19 14:11:35.123456 +02:00'],
                ['14:11:35', '1745071895'],
                ['14:11:35', '1745071895.123'],
                ['14:11:35', 1745071895],
                ['14:11:35', 1745071895.123],
                ['14:11:35', 51095],
                ['14:11:35', 51095.123456],
                ['12:11:35', new DateTimeImmutable('14:11:35 +02:00')],
                ['12:11:35', new DateTime('14:11:35 +02:00')],
                ['12:11:35', new Stringable('14:11:35 +02:00')],
                [$expression = new Expression("'14:11:35'"), $expression],
            ],
        ];
        $values['timetz6'] = [
            new DateTimeColumn(ColumnType::TIMETZ, size: 6),
            [
                [null, null],
                [null, ''],
                ['00:00:00.000000', '2025-04-19'],
                ['14:11:35.000000', '14:11:35'],
                ['14:11:35.123456', '14:11:35.123456'],
                ['12:11:35.000000', '14:11:35 +02:00'],
                ['12:11:35.123456', '14:11:35.123456 +02:00'],
                ['14:11:35.000000', '2025-04-19 14:11:35'],
                ['14:11:35.123456', '2025-04-19 14:11:35.123456'],
                ['12:11:35.000000', '2025-04-19 14:11:35 +02:00'],
                ['12:11:35.123456', '2025-04-19 14:11:35.123456 +02:00'],
                ['14:11:35.000000', '1745071895'],
                ['14:11:35.123000', '1745071895.123'],
                ['14:11:35.000000', 1745071895],
                ['14:11:35.123000', 1745071895.123],
                ['14:11:35.000000', 51095],
                ['14:11:35.123456', 51095.123456],
                ['12:11:35.123456', new DateTimeImmutable('14:11:35.123456 +02:00')],
                ['12:11:35.123456', new DateTime('14:11:35.123456 +02:00')],
                ['12:11:35.123456', new Stringable('14:11:35.123456 +02:00')],
                [$expression = new Expression("'14:11:35.123456'"), $expression],
            ],
        ];

        return $values;
    }

    public static function phpTypecastColumns(): array
    {
        $values = parent::phpTypecastColumns();
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP);
        $values['datetime'][0] = new DateTimeColumn();
        $values['datetimetz'][0] = new DateTimeColumn(ColumnType::DATETIMETZ);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);

        return $values;
    }

    public static function bigIntValue(): array
    {
        return [
            ['8817806877'],
            ['3797444208'],
            ['3199585540'],
            ['1389831585'],
            ['922337203685477580'],
            ['9223372036854775807'],
            ['-9223372036854775808'],
        ];
    }
}
