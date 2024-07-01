<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\Builder\AbstractOverlapsConditionBuilder;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;

/**
 * Builds expressions for {@see JsonOverlapsCondition} for MySQL Server.
 */
final class JsonOverlapsConditionBuilder extends AbstractOverlapsConditionBuilder
{
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
        $column = $this->prepareColumn($expression->getColumn());
        $values = $expression->getValues();

        if (!$values instanceof ExpressionInterface) {
            $values = new JsonExpression($values);
        }

        $values = $this->queryBuilder->buildExpression($values, $params);

        return "JSON_OVERLAPS($column, $values)";
    }
}
