<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\QueryBuilder\QueryBuilder as AbstractQueryBuilder;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_merge;

/**
 * The class QueryBuilder is the query builder for Mysql databases.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * Defines a FULLTEXT index type for {@see createIndex()}.
     */
    public const INDEX_FULLTEXT = 'FULLTEXT';

    /**
     * Defines a SPATIAL index type for {@see createIndex()}.
     */
    public const INDEX_SPATIAL = 'SPATIAL';

    /**
     * Defines a B-tree index method for {@see createIndex()}.
     */
    public const INDEX_B_TREE = 'btree';

    /**
     * Defines a hash index method for {@see createIndex()}.
     */
    public const INDEX_HASH = 'hash';

    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UPK => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'tinyint(3)',
        Schema::TYPE_SMALLINT => 'smallint(6)',
        Schema::TYPE_INTEGER => 'int(11)',
        Schema::TYPE_BIGINT => 'bigint(20)',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'double',
        Schema::TYPE_DECIMAL => 'decimal(10,0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'blob',
        Schema::TYPE_BOOLEAN => 'tinyint(1)',
        Schema::TYPE_MONEY => 'decimal(19,4)',
        Schema::TYPE_JSON => 'json',
    ];
    private DDLQueryBuilder $ddlBuilder;
    private DMLQueryBuilder $dmlBuilder;
    private DQLQueryBuilder $dqlBuilder;

    public function __construct(
        QuoterInterface $quoter,
        SchemaInterface $schema,
    ) {
        $this->ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $this->dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $this->dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);
        parent::__construct($quoter, $schema, $this->ddlBuilder, $this->dmlBuilder, $this->dqlBuilder);
    }

    public function getColumnType(ColumnSchemaBuilder|string $type): string
    {
        $this->typeMap = array_merge($this->typeMap, $this->defaultTimeTypeMap());
        return parent::getColumnType($type);
    }

    /**
     * Returns the map for default time type.
     *
     * If the version of MySQL is lower than 5.6.4, then the types will be without fractional seconds, otherwise with
     * fractional seconds.
     *
     * @psalm-return array<string, string>
     */
    private function defaultTimeTypeMap(): array
    {
        return [
            Schema::TYPE_DATETIME => 'datetime(0)',
            Schema::TYPE_TIMESTAMP => 'timestamp(0)',
            Schema::TYPE_TIME => 'time(0)',
        ];
    }
}
