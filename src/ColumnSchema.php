<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use JsonException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\ColumnSchema as AbstractColumnSchema;

use function json_decode;

/**
 * The class ColumnSchema for Mysql database.
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value.
     *
     * @throws JsonException
     *
     * @return mixed converted value.
     */
    public function phpTypecast($value)
    {
        if ($value === null) {
            return null;
        }

        if ($this->getType() === Schema::TYPE_JSON) {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value.
     *
     * @return mixed converted value. This may also be an array containing the value as the first element and the PDO
     * type as the second element.
     */
    public function dbTypecast($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->getDbType() === Schema::TYPE_JSON) {
            return new JsonExpression($value, $this->getType());
        }

        return $this->typecast($value);
    }
}
