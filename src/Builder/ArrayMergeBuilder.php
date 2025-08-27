<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function implode;
use function is_string;
use function rtrim;

/**
 * Builds SQL expressions which merge arrays for {@see ArrayMerge} objects.
 *
 * ```sql
 * (SELECT JSON_ARRAYAGG(value) AS value FROM (
 *     SELECT value FROM JSON_TABLE(operand1, '$[*]' COLUMNS(value json PATH '$')) AS t
 *     UNION
 *     SELECT value FROM JSON_TABLE(operand2, '$[*]' COLUMNS(value json PATH '$')) AS t
 * ) t)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<ArrayMerge>
 */
final class ArrayMergeBuilder extends MultiOperandFunctionBuilder
{
    private const DEFAULT_OPERAND_TYPE = 'json';

    /**
     * Builds a SQL expression which merges arrays from the given {@see ArrayMerge} object.
     *
     * @param ArrayMerge $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $operandType = $this->buildOperandType($expression->getType());
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperand = $this->buildOperand($operand, $params);

            $selects[] = "SELECT value FROM JSON_TABLE($builtOperand, '$[*]' COLUMNS(value $operandType PATH '$')) AS t";
        }

        $unions = implode(' UNION ', $selects);

        if ($expression->getOrdered()) {
            $unions .= ' ORDER BY value';
        }

        return '(SELECT JSON_ARRAYAGG(value) AS value FROM (' . $unions . ') AS t)';
    }

    private function buildOperandType(string|ColumnInterface $type): string
    {
        if (is_string($type)) {
            return $type === '' ? self::DEFAULT_OPERAND_TYPE : rtrim($type, '[]');
        }

        if ($type instanceof AbstractArrayColumn) {
            if ($type->getDimension() > 1) {
                return self::DEFAULT_OPERAND_TYPE;
            }

            $type = $type->getColumn();

            if ($type === null) {
                return self::DEFAULT_OPERAND_TYPE;
            }
        }

        return $this->queryBuilder->getColumnDefinitionBuilder()->buildType($type);
    }
}
