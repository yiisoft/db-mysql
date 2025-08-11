<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mysql\Builder\JsonOverlapsBuilder;
use Yiisoft\Db\Mysql\Builder\LikeBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

use function ctype_digit;

/**
 * Implements a DQL (Data Query Language) SQL statements for MySQL, MariaDB.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string)$limit);

            if ($this->hasOffset($offset)) {
                $sql .= ' OFFSET ' . ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string)$offset);
            }
        } elseif ($this->hasOffset($offset)) {
            /**
             * Limit isn't optional in MySQL.
             *
             * @link https://stackoverflow.com/a/271650/1106908
             * @link https://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $sql = 'LIMIT ' .
                ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string)$offset) .
                ', 18446744073709551615'; // 2^64-1
        }

        return $sql;
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit The given limit.
     *
     * @return bool Whether the limit is effective.
     */
    protected function hasLimit(mixed $limit): bool
    {
        /** In MySQL limit argument must be a non-negative integer constant */
        return ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset The given offset.
     *
     * @return bool Whether the offset is effective.
     */
    protected function hasOffset(mixed $offset): bool
    {
        /** In MySQL offset argument must be a non-negative integer constant */
        $offset = (string) $offset;
        return ctype_digit($offset) && $offset !== '0';
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
        ];
    }
}
