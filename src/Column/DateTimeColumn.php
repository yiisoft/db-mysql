<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;

/**
 * Represents the metadata for a datetime column.
 *
 * > [!WARNING]
 * > MySQL DBMS converts `TIMESTAMP` column type values from database session time zone to UTC for storage, and back
 * > from UTC to the session time zone when retrieve the values.
 *
 * `TIMESTAMP` database type does not store time zone offset and require to convert datetime values to the database
 * session time zone before insert and back to the PHP time zone after retrieve the values. This will be done in the
 * {@see dbTypecast()} and {@see phpTypecast()} methods and guarantees that the values are stored in the database
 * in the correct time zone.
 *
 * To avoid possible time zone issues with the datetime values conversion, it is recommended to set the PHP and database
 * time zones to UTC.
 */
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
