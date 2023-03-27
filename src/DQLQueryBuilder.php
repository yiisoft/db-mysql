<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;

use function array_merge;
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
             * @link http://stackoverflow.com/a/271650/1106908
             * @link http://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
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

    /**
     * Has an array of default expression builders.
     *
     * Extend this method and override it if you want to change default expression builders for this query builder.
     *
     * {@see ExpressionBuilder} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(
            parent::defaultExpressionBuilders(),
            [
                JsonExpression::class => JsonExpressionBuilder::class,
            ]
        );
    }
}
