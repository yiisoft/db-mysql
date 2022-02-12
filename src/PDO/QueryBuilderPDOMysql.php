<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\PDO;

use JsonException;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\DDLQueryBuilder;
use Yiisoft\Db\Mysql\DMLQueryBuilder;
use Yiisoft\Db\Mysql\JsonExpressionBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_merge;
use function ctype_digit;
use function reset;

/**
 * The class QueryBuilder is the query builder for Mysql databases.
 */
final class QueryBuilderPDOMysql extends QueryBuilder
{
    /**
     * @var array<string, string> mapping from abstract column types (keys) to physical column types (values).
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

    public function __construct(
        private CommandInterface $command,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        $this->ddlBuilder = new DDLQueryBuilder($this);
        $this->dmlBuilder = new DMLQueryBuilder($this);
        parent::__construct($quoter, $schema);
    }

    /**
     * @throws NotSupportedException
     */
    public function addCheck(string $name, string $table, string $expression): string
    {
        throw new NotSupportedException(QueryBuilderPDOMysql::class . '::addCheck is not supported by MySQL.');
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnColumn($table, $column, $comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnTable($table, $comment);
    }

    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . (string) $limit;

            if ($this->hasOffset($offset)) {
                $sql .= ' OFFSET ' . (string) $offset;
            }
        } elseif ($this->hasOffset($offset)) {
            /**
             * limit is not optional in MySQL.
             *
             * http://stackoverflow.com/a/271650/1106908
             * http://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $sql = "LIMIT $offset, 18446744073709551615"; // 2^64-1
        }

        return $sql;
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return 'SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0);
    }

    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return $this->ddlBuilder->createIndex($name, $table, $columns, $unique);
    }

    public function command(): CommandInterface
    {
        return $this->command;
    }

    /**
     * @throws NotSupportedException
     */
    public function dropCheck(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Exception
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->addCommentOnColumn($table, $column, '');
    }

    /**
     * @throws Exception
     */
    public function dropCommentFromTable(string $table): string
    {
        return $this->addCommentOnTable($table, '');
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return $this->ddlBuilder->dropForeignKey($name, $table);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return $this->ddlBuilder->dropPrimaryKey($name, $table);
    }

    public function dropUnique(string $name, string $table): string
    {
        return $this->dropIndex($name, $table);
    }

    public function getColumnType(ColumnSchemaBuilder|string $type): string
    {
        $this->typeMap = array_merge($this->typeMap, $this->defaultTimeTypeMap());
        return parent::getColumnType($type);
    }

    /**
     * Prepares a `VALUES` part for an `INSERT` SQL statement.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query|Query} to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return array array of column names, placeholders, values and params.
     */
    public function prepareInsertValues(string $table, Query|array $columns, array $params = []): array
    {
        /**
         * @var array $names
         * @var array $placeholders
         */
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($table, $columns, $params);

        if (!$columns instanceof Query && empty($names)) {
            $tableSchema = $this->schema->getTableSchema($table);

            if ($tableSchema !== null) {
                $columns = $tableSchema->getColumns();
                $columns = !empty($tableSchema->getPrimaryKey())
                    ? $tableSchema->getPrimaryKey() : [reset($columns)->getName()];
                /** @var string $name */
                foreach ($columns as $name) {
                    $names[] = $this->quoter->quoteColumnName($name);
                    $placeholders[] = 'DEFAULT';
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    public function quoter(): QuoterInterface
    {
        return $this->quoter;
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return $this->ddlBuilder->renameColumn($table, $oldName, $newName);
    }

    public function schema(): SchemaInterface
    {
        return $this->schema;
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit the given limit.
     *
     * @return bool whether the limit is effective.
     */
    protected function hasLimit(mixed $limit): bool
    {
        /** In MySQL limit argument must be non-negative integer constant */
        return ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset the given offset.
     *
     * @return bool whether the offset is effective.
     */
    protected function hasOffset(mixed $offset): bool
    {
        /** In MySQL offset argument must be non-negative integer constant */
        $offset = (string) $offset;
        return ctype_digit($offset) && $offset !== '0';
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see ExpressionBuilder} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(
            parent::defaultExpressionBuilders(),
            [
                JsonExpression::class => JsonExpressionBuilder::class,
            ]
        );
    }

    /**
     * Returns the map for default time type.
     *
     * If the version of MySQL is lower than 5.6.4, then the types will be without fractional seconds, otherwise with
     * fractional seconds.
     *
     * @return array
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
