<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expressions\ExpressionBuilderInterface;
use Yiisoft\Db\Expressions\ExpressionBuilderTrait;
use Yiisoft\Db\Expressions\ExpressionInterface;
use Yiisoft\Db\Expressions\JsonExpression;
use Yiisoft\Db\Querys\Query;
use Yiisoft\Json\Json;

/**
 * Class JsonExpressionBuilder builds {@see JsonExpression} for MySQL DBMS.
 */
class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    public const PARAM_PREFIX = ':qp';

    /**
     * {@inheritdoc}
     *
     * @param JsonExpression|ExpressionInterface $expression the expression to be built
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $value = $expression->getValue();

        if ($value instanceof Query) {
            list($sql, $params) = $this->queryBuilder->build($value, $params);

            return "($sql)";
        }

        $placeholder = static::PARAM_PREFIX . count($params);
        $params[$placeholder] = Json::encode($value);

        return "CAST($placeholder AS JSON)";
    }
}
