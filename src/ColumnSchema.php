<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use JsonException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function json_decode;

/**
 * Represents the metadata of a column in a database table for Mysql, MariaDb Server. It provides information about the
 * column's name, type, size, precision, and other details.
 *
 * Is used to store and retrieve metadata about a column in a database table. It is typically used in conjunction with
 * the TableSchema class, which represents the metadata of a database table as a whole.
 *
 * Here is an example of how the ColumnSchema class might be used:
 *
 * ```php
 * use Yiisoft\Db\Mysql\ColumnSchema;
 *
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('int(11)');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ```
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is `null` or an {@see \Yiisoft\Db\Expression\Expression}, it will not be converted.
     *
     * @param mixed $value The value to be converted.
     *
     * @throws JsonException If the value cannot be decoded.
     *
     * @return mixed The converted value.
     */
    public function phpTypecast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($this->getType() === SchemaInterface::TYPE_JSON) {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is `null` or an {@see \Yiisoft\Db\Expression\Expression}, it will not be converted.
     *
     * @param mixed $value The value to be converted.
     *
     * @return mixed The converted value. This may also be an array containing the value as the first element and the
     * PDO type as the second element.
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->getDbType() === SchemaInterface::TYPE_JSON) {
            return new JsonExpression($value, $this->getType());
        }

        return $this->typecast($value);
    }
}
