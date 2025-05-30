<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\Like;

/**
 * Build an object of {@see Like} into SQL expressions for MySQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function prepareColumn(Like $condition, array &$params): string
    {
        $column = parent::prepareColumn($condition, $params);

        if ($condition->caseSensitive === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
