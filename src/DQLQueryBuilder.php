<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Mysql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\Query\DQLQueryBuilder as AbstractDQLQueryBuilder;

use function array_merge;
use function ctype_digit;

final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . (string) $limit;

            if ($this->hasOffset($offset)) {
                $sql .= ' OFFSET ' . (string) $offset;
            }
        } elseif ($this->hasOffset($offset)) {
            /**
             * limit is not optional in MySQL.
             *
             * http://stackoverflow.com/a/271650/1106908
             * http://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $sql = "LIMIT $offset, 18446744073709551615"; // 2^64-1
        }

        return $sql;
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit the given limit.
     *
     * @return bool whether the limit is effective.
     */
    protected function hasLimit(mixed $limit): bool
    {
        /** In MySQL limit argument must be non-negative integer constant */
        return ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset the given offset.
     *
     * @return bool whether the offset is effective.
     */
    protected function hasOffset(mixed $offset): bool
    {
        /** In MySQL offset argument must be non-negative integer constant */
        $offset = (string) $offset;
        return ctype_digit($offset) && $offset !== '0';
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * See {@see ExpressionBuilder} docs for details.
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
