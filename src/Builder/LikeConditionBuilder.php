<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

/**
 * Build an object of {@see LikeCondition} into SQL expressions for MySQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    protected function prepareColumn(LikeCondition $expression, array &$params): string
    {
        $column = parent::prepareColumn($expression, $params);

        if ($expression->caseSensitive === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
