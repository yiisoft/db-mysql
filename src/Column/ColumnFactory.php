<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function bindec;
use function in_array;
use function preg_match;
use function str_starts_with;
use function substr;

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
        'tinyblob' => ColumnType::BINARY,
        'mediumblob' => ColumnType::BINARY,
        'longblob' => ColumnType::BINARY,
        'year' => ColumnType::SMALLINT,
        'timestamp' => ColumnType::TIMESTAMP,
        'datetime' => ColumnType::DATETIME,
        'time' => ColumnType::TIME,
        'date' => ColumnType::DATE,
        'json' => ColumnType::JSON,
    ];

    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::CHAR => StringColumn::class,
            ColumnType::STRING => StringColumn::class,
            ColumnType::TEXT => StringColumn::class,
            ColumnType::UUID => StringColumn::class,
            ColumnType::TIMESTAMP => DateTimeColumn::class,
            ColumnType::DATETIME => DateTimeColumn::class,
            ColumnType::DATETIMETZ => DateTimeColumn::class,
            ColumnType::TIME => DateTimeColumn::class,
            ColumnType::TIMETZ => DateTimeColumn::class,
            ColumnType::DATE => DateTimeColumn::class,
            ColumnType::DECIMAL => StringColumn::class,
            default => parent::getColumnClass($type, $info),
        };
    }

    protected function getType(string $dbType, array $info = []): string
    {
        if ($dbType === 'bit' && isset($info['size']) && $info['size'] === 1) {
            return ColumnType::BOOLEAN;
        }

        return parent::getType($dbType, $info);
    }

    protected function normalizeDefaultValue(?string $defaultValue, ColumnInterface $column): mixed
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

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        if ($defaultValue === '') {
            return $column->phpTypecast($defaultValue);
        }

        if (
            in_array($column->getType(), [ColumnType::TIMESTAMP, ColumnType::DATETIME, ColumnType::DATE, ColumnType::TIME], true)
            && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', $defaultValue, $matches) === 1
        ) {
            return new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1]) ? '(' . $matches[1] . ')' : ''));
        }

        if (!empty($column->getExtra())
            || $defaultValue[0] === '('
            && !in_array($column->getType(), [ColumnType::CHAR, ColumnType::STRING, ColumnType::TEXT, ColumnType::BINARY], true)
        ) {
            return new Expression($defaultValue);
        }

        if ($defaultValue[0] === "'" && $defaultValue[-1] === "'") {
            $value = substr($defaultValue, 1, -1);
            $value = str_replace("''", "'", $value);

            return $column->phpTypecast($value);
        }

        if (
            str_starts_with($defaultValue, "b'")
            && in_array($column->getType(), [ColumnType::BOOLEAN, ColumnType::BIT], true)
        ) {
            return $column->phpTypecast(bindec(substr($defaultValue, 2, -1)));
        }

        return $column->phpTypecast($defaultValue);
    }
}
