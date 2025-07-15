<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;

/**
 * Build an object of {@see LikeConditionInterface} into SQL expressions for MySQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    /**+
     * @param LikeConditionInterface $expression
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function prepareColumn(ExpressionInterface $expression, array &$params): string
    {
        $column = parent::prepareColumn($expression, $params);

        if ($expression->getCaseSensitive() === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
