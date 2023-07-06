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
 * Represents the metadata of a column in a database table for MySQL, MariaDB.
 *
 * It provides information about the column's name, type, size, precision, and other details.
 *
 * It's used to store and retrieve metadata about a column in a database table, typically in conjunction with
 * the {@see TableSchema}, which represents the metadata of a database table as a whole.
 *
 * The following code shows how to use:
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
     * If the value is `null` or an {@see \Yiisoft\Db\Expression\Expression}, it won't be converted.
     *
     * @param mixed $value The value to convert.
     *
     * @throws JsonException If the value can't be decoded.
     *
     * @return mixed The converted value.
     */
    public function phpTypecast(mixed $value): mixed
    {
        return match (true) {
            $value === null
                => null,
            $this->getType() === SchemaInterface::TYPE_JSON
                => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default
            => parent::phpTypecast($value),
        };
    }

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is `null` or an {@see \Yiisoft\Db\Expression\Expression}, it won't be converted.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The converted value. This may also be an array containing the value as the first element and the
     * PDO type as the second element.
     */
    public function dbTypecast(mixed $value): mixed
    {
        return match (true) {
            $value === null
                => null,
            $value instanceof ExpressionInterface
                => $value,
            $this->getType() === SchemaInterface::TYPE_JSON
                => new JsonExpression($value, $this->getDbType()),
            default
            => parent::dbTypecast($value),
        };
    }
}
