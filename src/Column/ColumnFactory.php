<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\SchemaInterface;

class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     *
     * @psalm-suppress MissingClassConstType
     */
    private const TYPE_MAP = [
        'bit' => SchemaInterface::TYPE_BIT,
        'tinyint' => SchemaInterface::TYPE_TINYINT,
        'smallint' => SchemaInterface::TYPE_SMALLINT,
        'mediumint' => SchemaInterface::TYPE_INTEGER,
        'int' => SchemaInterface::TYPE_INTEGER,
        'integer' => SchemaInterface::TYPE_INTEGER,
        'bigint' => SchemaInterface::TYPE_BIGINT,
        'float' => SchemaInterface::TYPE_FLOAT,
        'real' => SchemaInterface::TYPE_FLOAT,
        'double' => SchemaInterface::TYPE_DOUBLE,
        'decimal' => SchemaInterface::TYPE_DECIMAL,
        'numeric' => SchemaInterface::TYPE_DECIMAL,
        'char' => SchemaInterface::TYPE_CHAR,
        'varchar' => SchemaInterface::TYPE_STRING,
        'string' => SchemaInterface::TYPE_STRING,
        'enum' => SchemaInterface::TYPE_STRING,
        'tinytext' => SchemaInterface::TYPE_TEXT,
        'mediumtext' => SchemaInterface::TYPE_TEXT,
        'longtext' => SchemaInterface::TYPE_TEXT,
        'text' => SchemaInterface::TYPE_TEXT,
        'varbinary' => SchemaInterface::TYPE_BINARY,
        'blob' => SchemaInterface::TYPE_BINARY,
        'longblob' => SchemaInterface::TYPE_BINARY,
        'year' => SchemaInterface::TYPE_DATE,
        'date' => SchemaInterface::TYPE_DATE,
        'time' => SchemaInterface::TYPE_TIME,
        'datetime' => SchemaInterface::TYPE_DATETIME,
        'timestamp' => SchemaInterface::TYPE_TIMESTAMP,
        'json' => SchemaInterface::TYPE_JSON,
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
        $type = self::TYPE_MAP[$dbType] ?? SchemaInterface::TYPE_STRING;

        if ($type === SchemaInterface::TYPE_BIT && isset($info['size']) && $info['size'] === 1) {
            return SchemaInterface::TYPE_BOOLEAN;
        }

        return $type;
    }
}
