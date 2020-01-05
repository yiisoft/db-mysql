<?php
declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\ExpressionBuilderInterface;
use Yiisoft\Db\ExpressionBuilderTrait;
use Yiisoft\Db\ExpressionInterface;
use Yiisoft\Db\JsonExpression;
use Yiisoft\Db\Query;
use Yiisoft\Json\Json;

/**
 * Class JsonExpressionBuilder builds {@see JsonExpression} for MySQL DBMS.
 */
class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    const PARAM_PREFIX = ':qp';

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

        $placeholder = static::PARAM_PREFIX.count($params);
        $params[$placeholder] = Json::encode($value);

        return "CAST($placeholder AS JSON)";
    }
}
