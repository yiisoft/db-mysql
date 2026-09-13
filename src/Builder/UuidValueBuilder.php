<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds a {@see UuidValue} expression for MySQL and MariaDB.
 *
 * Both store a UUID as 16 raw bytes in a `binary(16)` column, so the canonical string form is converted to bytes and
 * bound as {@see DataType::LOB}.
 *
 * @implements ExpressionBuilderInterface<UuidValue>
 */
final class UuidValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @var UuidValue $expression */
        return $this->queryBuilder->bindParam(
            new Param(DbUuidHelper::uuidToBlob($expression->value), DataType::LOB),
            $params,
        );
    }
}
