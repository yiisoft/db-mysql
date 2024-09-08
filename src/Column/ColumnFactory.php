<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     *
     * @psalm-suppress MissingClassConstType
     */
    private const TYPE_MAP = [
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
        'string' => ColumnType::STRING,
        'enum' => ColumnType::STRING,
        'tinytext' => ColumnType::TEXT,
        'mediumtext' => ColumnType::TEXT,
        'longtext' => ColumnType::TEXT,
        'text' => ColumnType::TEXT,
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

    public function fromDefinition(string $definition, array $info = []): ColumnSchemaInterface
    {
        if (str_starts_with($definition, 'enum(')) {
            preg_match('/^enum\(([^)]+)\)\s*/', $definition, $matches);
            preg_match_all("/'([^']*)'/", $matches[1], $values);

            $info['enum_values'] = $values[1];

            return $this->fromDbType('enum', $info);
        }

        return parent::fromDefinition($definition, $info);
    }

    protected function getType(string $dbType, array $info = []): string
    {
        $type = self::TYPE_MAP[$dbType] ?? ColumnType::STRING;

        if ($type === ColumnType::BIT && isset($info['size']) && $info['size'] === 1) {
            return ColumnType::BOOLEAN;
        }

        return $type;
    }

    protected function isDbType(string $dbType): bool
    {
        return isset(self::TYPE_MAP[$dbType]);
    }
}
