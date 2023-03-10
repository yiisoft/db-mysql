<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use JsonException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Json\Json;

use function count;

/**
 * Builds expressions for {@see `Yiisoft\Db\Expression\JsonExpression`} for MySQL, MariaDb Server.
 */
final class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    public const PARAM_PREFIX = ':qp';

    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @psalm-param JsonExpression $expression
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @psalm-var mixed|QueryInterface $value */
        $value = $expression->getValue();

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);

            return "($sql)";
        }

        $placeholder = self::PARAM_PREFIX . count($params);
        $params[$placeholder] = Json::encode($value);

        return $placeholder;
    }
}
