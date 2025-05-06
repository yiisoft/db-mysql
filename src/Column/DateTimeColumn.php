<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;

final class DateTimeColumn extends \Yiisoft\Db\Schema\Column\DateTimeColumn
{
    protected function getFormat(): string
    {
        return $this->format ??= match ($this->getType()) {
            ColumnType::DATETIMETZ => 'Y-m-d H:i:s' . $this->getMillisecondsFormat(),
            ColumnType::TIMETZ => 'H:i:s' . $this->getMillisecondsFormat(),
            default => parent::getFormat(),
        };
    }

    protected function shouldConvertTimezone(): bool
    {
        return $this->shouldConvertTimezone ??= !empty($this->dbTimezone) && $this->getType() !== ColumnType::DATE;
    }
}
