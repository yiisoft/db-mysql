<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Mysql\Column\DateTimeColumn;
use Yiisoft\Db\Mysql\Column\StringColumn;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['char()'][2] = StringColumn::class;
        $values['char(100)'][2] = StringColumn::class;
        $values['string()'][2] = StringColumn::class;
        $values['string(100)'][2] = StringColumn::class;
        $values['text()'][2] = StringColumn::class;
        $values['text(5000)'][2] = StringColumn::class;
        $values['uuid()'][2] = StringColumn::class;
        $values['timestamp()'][2] = DateTimeColumn::class;
        $values['timestamp(3)'][2] = DateTimeColumn::class;
        $values['datetime()'][2] = DateTimeColumn::class;
        $values['datetime(3)'][2] = DateTimeColumn::class;
        $values['datetimeWithTimezone()'][2] = DateTimeColumn::class;
        $values['datetimeWithTimezone(3)'][2] = DateTimeColumn::class;
        $values['time()'][2] = DateTimeColumn::class;
        $values['time(3)'][2] = DateTimeColumn::class;
        $values['timeWithTimezone()'][2] = DateTimeColumn::class;
        $values['timeWithTimezone(3)'][2] = DateTimeColumn::class;
        $values['date()'][2] = DateTimeColumn::class;

        return $values;
    }
}
