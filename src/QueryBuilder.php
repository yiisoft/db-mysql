<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Mysql\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * Implements MySQL, MariaDB specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        PseudoType::PK => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        PseudoType::UPK => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        PseudoType::BIGPK => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        PseudoType::UBIGPK => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        ColumnType::CHAR => 'char(1)',
        ColumnType::STRING => 'varchar(255)',
        ColumnType::TEXT => 'text',
        ColumnType::TINYINT => 'tinyint(3)',
        ColumnType::SMALLINT => 'smallint(6)',
        ColumnType::INTEGER => 'int(11)',
        ColumnType::BIGINT => 'bigint(20)',
        ColumnType::FLOAT => 'float',
        ColumnType::DOUBLE => 'double',
        ColumnType::DECIMAL => 'decimal(10,0)',
        ColumnType::DATE => 'date',
        ColumnType::BINARY => 'blob',
        ColumnType::BOOLEAN => 'bit(1)',
        ColumnType::MONEY => 'decimal(19,4)',
        ColumnType::JSON => 'json',
        ColumnType::DATETIME => 'datetime(0)',
        ColumnType::TIMESTAMP => 'timestamp(0)',
        ColumnType::TIME => 'time(0)',
        ColumnType::UUID => 'binary(16)',
        PseudoType::UUID_PK => 'binary(16) PRIMARY KEY',
    ];

    public function __construct(
        QuoterInterface $quoter,
        SchemaInterface $schema,
    ) {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter);
        $columnDefinitionBuilder = new ColumnDefinitionBuilder($this);

        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder, $columnDefinitionBuilder);
    }
}
