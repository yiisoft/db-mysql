<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonOverlapsCondition} for MySQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonOverlapsCondition>
 */
final class JsonOverlapsConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see JsonOverlapsCondition}.
     *
     * @param JsonOverlapsCondition $expression The {@see JsonOverlapsCondition} to be built.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if (!$values instanceof ExpressionInterface) {
            $values = new JsonExpression($values);
        }

        $values = $this->queryBuilder->buildExpression($values, $params);

        return "JSON_OVERLAPS($column, $values)";
    }
}
