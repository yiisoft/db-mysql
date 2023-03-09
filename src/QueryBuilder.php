<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\ColumnSchemaBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_merge;

/**
 * Implements the MySQL, MariaDb Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        SchemaInterface::TYPE_PK => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        SchemaInterface::TYPE_UPK => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        SchemaInterface::TYPE_BIGPK => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        SchemaInterface::TYPE_UBIGPK => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        SchemaInterface::TYPE_CHAR => 'char(1)',
        SchemaInterface::TYPE_STRING => 'varchar(255)',
        SchemaInterface::TYPE_TEXT => 'text',
        SchemaInterface::TYPE_TINYINT => 'tinyint(3)',
        SchemaInterface::TYPE_SMALLINT => 'smallint(6)',
        SchemaInterface::TYPE_INTEGER => 'int(11)',
        SchemaInterface::TYPE_BIGINT => 'bigint(20)',
        SchemaInterface::TYPE_FLOAT => 'float',
        SchemaInterface::TYPE_DOUBLE => 'double',
        SchemaInterface::TYPE_DECIMAL => 'decimal(10,0)',
        SchemaInterface::TYPE_DATE => 'date',
        SchemaInterface::TYPE_BINARY => 'blob',
        SchemaInterface::TYPE_BOOLEAN => 'bit(1)',
        SchemaInterface::TYPE_MONEY => 'decimal(19,4)',
        SchemaInterface::TYPE_JSON => 'json',
        SchemaInterface::TYPE_DATETIME => 'datetime(0)',
        SchemaInterface::TYPE_TIMESTAMP => 'timestamp(0)',
        SchemaInterface::TYPE_TIME => 'time(0)',
    ];

    public function __construct(
        QuoterInterface $quoter,
        SchemaInterface $schema,
    ) {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);
        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
    }

    public function getColumnType(ColumnSchemaBuilderInterface|string $type): string
    {
        if ($type instanceof ColumnSchemaBuilderInterface && $type->getType() === SchemaInterface::TYPE_JSON) {
            $type->check('[[{name}]] is null or json_valid([[{name}]])');
            $type = SchemaInterface::TYPE_JSON;
        }

        return parent::getColumnType($type);
    }
}
