<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\Builder\UuidValueBuilder as BaseUuidValueBuilder;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;

/**
 * Builds a {@see UuidValue} expression for MySQL and MariaDB.
 *
 * Both store a UUID as 16 raw bytes in a `binary(16)` column, so the canonical string form is converted to bytes and
 * bound as {@see DataType::LOB}.
 */
final class UuidValueBuilder extends BaseUuidValueBuilder
{
    protected function prepareValue(UuidValue $expression): Param
    {
        return new Param(DbUuidHelper::uuidToBlob($expression->value), DataType::LOB);
    }
}
