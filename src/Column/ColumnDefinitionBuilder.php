<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'AUTO_INCREMENT';

    protected const GENERATE_UUID_EXPRESSION = "unhex(replace(uuid(),'-',''))";

    protected const TYPES_WITH_SIZE = [
        'bit',
        'tinyint',
        'smallint',
        'mediumint',
        'int',
        'integer',
        'bigint',
        'float',
        'real',
        'double',
        'double precision',
        'decimal',
        'numeric',
        'dec',
        'fixed',
        'char',
        'character',
        'national char',
        'nchar',
        'varchar',
        'character varying',
        'national varchar',
        'nvarchar',
        'text',
        'binary',
        'char byte',
        'varbinary',
        'blob',
        'year',
        'time',
        'datetime',
        'timestamp',
    ];

    protected const TYPES_WITH_SCALE = [
        'float',
        'real',
        'double',
        'double precision',
        'decimal',
        'numeric',
        'dec',
        'fixed',
    ];

    protected function buildComment(ColumnSchemaInterface $column): string
    {
        $comment = $column->getComment();

        return $comment === null ? '' : ' COMMENT ' . $this->queryBuilder->quoter()->quoteValue($comment);
    }

    protected function getDbType(ColumnSchemaInterface $column): string
    {
        /** @psalm-suppress DocblockTypeContradiction */
        $dbType = $column->getDbType() ?? match ($column->getType()) {
            ColumnType::BOOLEAN => 'bit(1)',
            ColumnType::BIT => 'bit',
            ColumnType::TINYINT => 'tinyint',
            ColumnType::SMALLINT => 'smallint',
            ColumnType::INTEGER => 'int',
            ColumnType::BIGINT => 'bigint',
            ColumnType::FLOAT => 'float',
            ColumnType::DOUBLE => 'double',
            ColumnType::DECIMAL => 'decimal',
            ColumnType::MONEY => 'decimal',
            ColumnType::CHAR => 'char',
            ColumnType::STRING => 'varchar(' . ($column->getSize() ?? 255) . ')',
            ColumnType::TEXT => 'text',
            ColumnType::BINARY => 'blob',
            ColumnType::UUID => 'binary(16)',
            ColumnType::DATETIME => 'datetime',
            ColumnType::TIMESTAMP => 'timestamp',
            ColumnType::DATE => 'date',
            ColumnType::TIME => 'time',
            ColumnType::ARRAY => 'json',
            ColumnType::STRUCTURED => 'json',
            ColumnType::JSON => 'json',
            default => 'varchar',
        };

        if ($dbType === 'double' && $column->getSize() !== null) {
            return 'double(' . $column->getSize() . ',' . ($column->getScale() ?? 0) . ')';
        }

        return $dbType;
    }
}
