<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function bindec;
use function in_array;
use function preg_match;
use function substr;
use function trim;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [
        'bit' => ColumnType::BIT,
        'tinyint' => ColumnType::TINYINT,
        'smallint' => ColumnType::SMALLINT,
        'mediumint' => ColumnType::INTEGER,
        'int' => ColumnType::INTEGER,
        'integer' => ColumnType::INTEGER,
        'bigint' => ColumnType::BIGINT,
        'float' => ColumnType::FLOAT,
        'real' => ColumnType::FLOAT,
        'double' => ColumnType::DOUBLE,
        'decimal' => ColumnType::DECIMAL,
        'numeric' => ColumnType::DECIMAL,
        'char' => ColumnType::CHAR,
        'varchar' => ColumnType::STRING,
        'enum' => ColumnType::STRING,
        'tinytext' => ColumnType::TEXT,
        'mediumtext' => ColumnType::TEXT,
        'longtext' => ColumnType::TEXT,
        'text' => ColumnType::TEXT,
        'binary' => ColumnType::BINARY,
        'varbinary' => ColumnType::BINARY,
        'blob' => ColumnType::BINARY,
        'longblob' => ColumnType::BINARY,
        'year' => ColumnType::DATE,
        'date' => ColumnType::DATE,
        'time' => ColumnType::TIME,
        'datetime' => ColumnType::DATETIME,
        'timestamp' => ColumnType::TIMESTAMP,
        'json' => ColumnType::JSON,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        if ($dbType === 'bit' && isset($info['size']) && $info['size'] === 1) {
            return ColumnType::BOOLEAN;
        }

        return parent::getType($dbType, $info);
    }

    protected function normalizeDefaultValue(string|null $defaultValue, ColumnSchemaInterface $column): mixed
    {
        if (
            $defaultValue === null
            || $column->isPrimaryKey()
            || $column->isComputed()
        ) {
            return null;
        }

        return $this->normalizeNotNullDefaultValue($defaultValue, $column);
    }

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnSchemaInterface $column): mixed
    {
        if (
            in_array($column->getType(), [ColumnType::TIMESTAMP, ColumnType::DATETIME, ColumnType::DATE, ColumnType::TIME], true)
            && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', $defaultValue, $matches) === 1
        ) {
            return new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1]) ? '(' . $matches[1] . ')' : ''));
        }

        if (!empty($defaultValue) && !empty($column->getExtra())) {
            return new Expression($defaultValue);
        }

        if ($column->getDbType() === 'bit') {
            return $column->phpTypecast(bindec(trim($defaultValue, "b'")));
        }

        if ($defaultValue[0] === "'" && $defaultValue[-1] === "'") {
            return $column->phpTypecast(substr($defaultValue, 1, -1));
        }

        return $column->phpTypecast($defaultValue);
    }
}
