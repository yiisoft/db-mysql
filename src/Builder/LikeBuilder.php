<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\Like;

/**
 * Build an object of {@see Like} into SQL expressions for MySQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected function prepareColumn(Like $expression, array &$params): string
    {
        $column = parent::prepareColumn($expression, $params);

        if ($expression->caseSensitive === true) {
            $column = 'BINARY ' . $column;
        }

        return $column;
    }
}
