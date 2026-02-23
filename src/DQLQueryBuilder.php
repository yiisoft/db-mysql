<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Mysql\Builder\ArrayMergeBuilder;
use Yiisoft\Db\Mysql\Builder\JsonOverlapsBuilder;
use Yiisoft\Db\Mysql\Builder\LikeBuilder;
use Yiisoft\Db\Mysql\Builder\LongestBuilder;
use Yiisoft\Db\Mysql\Builder\ShortestBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

/**
 * Implements a DQL (Data Query Language) SQL statements for MySQL, MariaDB.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildLimit(ExpressionInterface|int|null $limit, ExpressionInterface|int|null $offset): string
    {
        if (!empty($offset)) {
            /**
             * Limit isn't optional in MySQL.
             *
             * @link https://stackoverflow.com/a/271650/1106908
             * @link https://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $limit = $limit instanceof ExpressionInterface
                ? $this->buildExpression($limit)
                : $limit ?? '18446744073709551615'; // 2^64-1

            $offset = $offset instanceof ExpressionInterface
                ? $this->buildExpression($offset)
                : (string) $offset;

            return "LIMIT $limit OFFSET $offset";
        }

        if ($limit !== null) {
            $limit = $limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string) $limit;

            return "LIMIT $limit";
        }

        return '';
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
            Longest::class => LongestBuilder::class,
            Shortest::class => ShortestBuilder::class,
        ];
    }
}
