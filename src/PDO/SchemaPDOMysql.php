<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\PDO;

use JsonException;
use PDO;
use PDOException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Mysql\ColumnSchemaBuilder;
use Yiisoft\Db\Mysql\TableSchema;
use Yiisoft\Db\Schema\Schema;

use function array_change_key_case;
use function array_map;
use function array_merge;
use function array_values;
use function bindec;
use function explode;
use function md5;
use function preg_match;
use function preg_match_all;
use function serialize;
use function str_replace;
use function stripos;
use function strtolower;
use function trim;

/**
 * The class Schema is the class for retrieving metadata from a Mysql database (version 5.7 and above).
 *
 * @psalm-type ColumnArray = array{
 *   table_schema: string,
 *   table_name: string,
 *   column_name: string,
 *   data_type: string,
 *   type_type: string|null,
 *   character_maximum_length: int,
 *   column_comment: string|null,
 *   modifier: int,
 *   is_nullable: bool,
 *   column_default: mixed,
 *   is_autoinc: bool,
 *   sequence_name: string|null,
 *   enum_values: array<array-key, float|int|string>|string|null,
 *   numeric_precision: int|null,
 *   numeric_scale: int|null,
 *   size: string|null,
 *   is_pkey: bool|null,
 *   dimension: int
 * }
 *
 * @psalm-type ColumnInfoArray = array{
 *   field: string,
 *   type: string,
 *   collation: string|null,
 *   null: string,
 *   key: string,
 *   default: string|null,
 *   extra: string,
 *   privileges: string,
 *   comment: string
 * }
 *
 * @psalm-type RowConstraint = array{
 *   constraint_name: string,
 *   column_name: string,
 *   referenced_table_name: string,
 *   referenced_column_name: string
 * }
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string
 *   }
 * >
 */
final class SchemaPDOMysql extends Schema
{
    /** @var array<array-key, string> $typeMap */
    private array $typeMap = [
        'tinyint' => self::TYPE_TINYINT,
        'bit' => self::TYPE_INTEGER,
        'smallint' => self::TYPE_SMALLINT,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'longblob' => self::TYPE_BINARY,
        'blob' => self::TYPE_BINARY,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'char' => self::TYPE_CHAR,
        'datetime' => self::TYPE_DATETIME,
        'year' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'enum' => self::TYPE_STRING,
        'varbinary' => self::TYPE_BINARY,
        'json' => self::TYPE_JSON,
    ];

    public function __construct(private ConnectionPDOInterface $db, SchemaCache $schemaCache)
    {
        parent::__construct($schemaCache);
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string|null $length length or precision of the column. See {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     */
    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length, $this->db->getQuoter());
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchema $table): array
    {
        $sql = $this->getCreateTableSql($table);

        $uniqueIndexes = [];

        $regexp = '/UNIQUE KEY\s+`(.+)`\s*\((`.+`)+\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = $match[1];
                $indexColumns = array_map('trim', explode('`,`', trim($match[2], '`')));
                $uniqueIndexes[$indexName] = $indexColumns;
            }
        }

        return $uniqueIndexes;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     *
     * @throws InvalidCallException if the DB connection is not active
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID(string $sequenceName = ''): string
    {
        $pdo = $this->db->getPDO();

        if ($this->db->isActive() && $pdo !== null) {
            return $pdo->lastInsertId(
                $sequenceName === '' ? null : $this->db->getQuoter()->quoteTableName($sequenceName)
            );
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $name): string
    {
        if (str_contains($name, '{{')) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->db->getTablePrefix(), $name);
        }

        return $name;
    }

    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function releaseSavepoint(string $name): void
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    public function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    public function supportsSavepoint(): bool
    {
        return $this->db->isSavepointEnabled();
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|Throwable if DB query fails.
     *
     * @return bool whether the table exists in the database.
     */
    protected function findColumns(TableSchema $table): bool
    {
        $tableName = $table->getFullName() ?? '';
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->db->getQuoter()->quoteTableName($tableName);

        try {
            $columns = $this->db->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof PDOException && str_contains($previous->getMessage(), 'SQLSTATE[42S02')) {
                /**
                 * table does not exist.
                 *
                 * https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                 */
                return false;
            }

            throw $e;
        }

        $slavePdo = $this->db->getSlavePdo();

        /** @psalm-var ColumnInfoArray $info */
        foreach ($columns as $info) {
            if ($slavePdo !== null && $slavePdo->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_LOWER) {
                $info = array_change_key_case($info, CASE_LOWER);
            }

            $column = $this->loadColumnSchema($info);
            $table->columns($column->getName(), $column);

            if ($column->isPrimaryKey()) {
                $table->primaryKey($column->getName());
                if ($column->isAutoIncrement()) {
                    $table->sequenceName('');
                }
            }
        }

        return true;
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|Throwable
     */
    protected function findConstraints(TableSchema $table): void
    {
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `constraint_name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `kcu`.`REFERENCED_TABLE_NAME` AS `referenced_table_name`,
            `kcu`.`REFERENCED_COLUMN_NAME` AS `referenced_column_name`
        FROM `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc`
        JOIN `information_schema`.`KEY_COLUMN_USAGE` AS `kcu` ON
            (
                `kcu`.`CONSTRAINT_CATALOG` = `rc`.`CONSTRAINT_CATALOG` OR
                (
                    `kcu`.`CONSTRAINT_CATALOG` IS NULL AND
                    `rc`.`CONSTRAINT_CATALOG` IS NULL
                )
            ) AND
            `kcu`.`CONSTRAINT_SCHEMA` = `rc`.`CONSTRAINT_SCHEMA` AND
            `kcu`.`CONSTRAINT_NAME` = `rc`.`CONSTRAINT_NAME` AND
            `kcu`.`TABLE_SCHEMA` = `rc`.`CONSTRAINT_SCHEMA` AND
            `kcu`.`TABLE_NAME` = `rc`.`TABLE_NAME`
        WHERE
            `rc`.`CONSTRAINT_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
            `rc`.`TABLE_NAME` = :tableName
        SQL;

        try {
            $rows = $this->db->createCommand($sql, [
                ':schemaName' => $table->getSchemaName(),
                ':tableName' => $table->getName(),
            ])->queryAll();

            $constraints = [];

            /**  @psalm-var RowConstraint $row */
            foreach ($rows as $row) {
                $constraints[$row['constraint_name']]['referenced_table_name'] = $row['referenced_table_name'];
                $constraints[$row['constraint_name']]['columns'][$row['column_name']] = $row['referenced_column_name'];
            }

            $table->foreignKeys([]);

            /**
             * @var array{referenced_table_name: string, columns: array} $constraint
             */
            foreach ($constraints as $name => $constraint) {
                $table->foreignKey($name, array_merge(
                    [$constraint['referenced_table_name']],
                    $constraint['columns']
                ));
            }
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if (!$previous instanceof PDOException || !str_contains($previous->getMessage(), 'SQLSTATE[42S02')) {
                throw $e;
            }

            // table does not exist, try to determine the foreign keys using the table creation sql
            $sql = $this->getCreateTableSql($table);
            $regexp = '/FOREIGN KEY\s+\(([^)]+)\)\s+REFERENCES\s+([^(^\s]+)\s*\(([^)]+)\)/mi';

            if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fks = array_map('trim', explode(',', str_replace('`', '', $match[1])));
                    $pks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                    $constraint = [str_replace('`', '', $match[2])];

                    foreach ($fks as $k => $name) {
                        $constraint[$name] = $pks[$k];
                    }

                    $table->foreignKey(md5(serialize($constraint)), $constraint);
                }
                $table->foreignKeys(array_values($table->getForeignKeys()));
            }
        }
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        $sql = 'SHOW TABLES';

        if ($schema !== '') {
            $sql .= ' FROM ' . $this->db->getQuoter()->quoteSimpleTableName($schema);
        }

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [
            __CLASS__,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
            $this->getRawTableName($name),
        ];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([
            __CLASS__,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
        ]));
    }

    /**
     * Gets the CREATE TABLE sql string.
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return string $sql the result of 'SHOW CREATE TABLE'.
     */
    protected function getCreateTableSql(TableSchema $table): string
    {
        $tableName = $table->getFullName() ?? '';

        /** @var array<array-key, string> $row */
        $row = $this->db->createCommand(
            'SHOW CREATE TABLE ' . $this->db->getQuoter()->quoteTableName($tableName)
        )->queryOne();

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        return $sql;
    }

    public function insert(string $table, array $columns): bool|array
    {
        $command = $this->db->createCommand()->insert($table, $columns);
        $tablePrimaryKey = [];

        if (!$command->execute()) {
            return false;
        }

        $tableSchema = $this->getTableSchema($table);
        $result = [];

        if ($tableSchema !== null) {
            $tablePrimaryKey = $tableSchema->getPrimaryKey();
        }

        /** @var string $name */
        foreach ($tablePrimaryKey as $name) {
            if ($tableSchema?->getColumn($name)?->isAutoIncrement()) {
                $result[$name] = $this->getLastInsertID((string) $tableSchema?->getSequenceName());
                break;
            }

            /** @var mixed */
            $result[$name] = $columns[$name] ?? $tableSchema?->getColumn($name)?->getDefaultValue();
        }

        return $result;
    }

    /**
     * Loads the column information into a {@see ColumnSchema} object.
     *
     * @param array $info column information.
     *
     * @throws JsonException
     *
     * @return ColumnSchema the column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchema
    {
        $column = $this->createColumnSchema();

        /** @psalm-var ColumnInfoArray $info */
        $column->name($info['field']);
        $column->allowNull($info['null'] === 'YES');
        $column->primaryKey(str_contains($info['key'], 'PRI'));
        $column->autoIncrement(stripos($info['extra'], 'auto_increment') !== false);
        $column->comment($info['comment']);
        $column->dbType($info['type']);
        $column->unsigned(stripos($column->getDbType(), 'unsigned') !== false);
        $column->type(self::TYPE_STRING);

        if (preg_match('/^(\w+)(?:\(([^)]+)\))?/', $column->getDbType(), $matches)) {
            $type = strtolower($matches[1]);

            if (isset($this->typeMap[$type])) {
                $column->type($this->typeMap[$type]);
            }

            if (!empty($matches[2])) {
                if ($type === 'enum') {
                    preg_match_all("/'[^']*'/", $matches[2], $values);

                    foreach ($values[0] as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }

                    $column->enumValues($values);
                } else {
                    $values = explode(',', $matches[2]);
                    $column->precision((int) $values[0]);
                    $column->size((int) $values[0]);

                    if (isset($values[1])) {
                        $column->scale((int) $values[1]);
                    }

                    if ($column->getSize() === 1 && $type === 'tinyint') {
                        $column->type('boolean');
                    } elseif ($type === 'bit') {
                        if ($column->getSize() > 32) {
                            $column->type('bigint');
                        } elseif ($column->getSize() === 32) {
                            $column->type('integer');
                        }
                    }
                }
            }
        }

        $column->phpType($this->getColumnPhpType($column));

        if (!$column->isPrimaryKey()) {
            /**
             * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
             * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
             *
             * See details here: https://mariadb.com/kb/en/library/now/#description
             */
            if (
                ($column->getType() === 'timestamp' || $column->getType() === 'datetime')
                && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', (string) $info['default'], $matches)
            ) {
                $column->defaultValue(new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1])
                    ? '(' . $matches[1] . ')' : '')));
            } elseif (isset($type) && $type === 'bit') {
                $column->defaultValue(bindec(trim((string) $info['default'], 'b\'')));
            } else {
                $column->defaultValue($column->phpTypecast($info['default']));
            }
        }

        return $column;
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws NotSupportedException
     *
     * @return array check constraints for the given table.
     */
    protected function loadTableChecks(string $tableName): array
    {
        throw new NotSupportedException('MySQL does not support check constraints.');
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array|Constraint|null (Constraint|ForeignKeyConstraint)[]|Constraint|null constraints.
     */
    private function loadTableConstraints(string $tableName, string $returnType): array|Constraint|null
    {
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `tc`.`CONSTRAINT_TYPE` AS `type`,
        CASE
            WHEN :schemaName IS NULL AND `kcu`.`REFERENCED_TABLE_SCHEMA` = DATABASE() THEN NULL
        ELSE `kcu`.`REFERENCED_TABLE_SCHEMA`
        END AS `foreign_table_schema`,
            `kcu`.`REFERENCED_TABLE_NAME` AS `foreign_table_name`,
            `kcu`.`REFERENCED_COLUMN_NAME` AS `foreign_column_name`,
            `rc`.`UPDATE_RULE` AS `on_update`,
            `rc`.`DELETE_RULE` AS `on_delete`,
            `kcu`.`ORDINAL_POSITION` AS `position`
        FROM `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`
        JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc` ON
            `rc`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND
            `rc`.`TABLE_NAME` = `kcu`.`TABLE_NAME` AND
            `rc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME`
        JOIN `information_schema`.`TABLE_CONSTRAINTS` AS `tc` ON
            `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND
            `tc`.`TABLE_NAME` = `kcu`.`TABLE_NAME` AND
            `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME` AND
            `tc`.`CONSTRAINT_TYPE` = 'FOREIGN KEY'
        WHERE
            `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
            `kcu`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND
            `kcu`.`TABLE_NAME` = :tableName
        UNION
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `tc`.`CONSTRAINT_TYPE` AS `type`,
        NULL AS `foreign_table_schema`,
        NULL AS `foreign_table_name`,
        NULL AS `foreign_column_name`,
        NULL AS `on_update`,
        NULL AS `on_delete`,
            `kcu`.`ORDINAL_POSITION` AS `position`
        FROM `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`
        JOIN `information_schema`.`TABLE_CONSTRAINTS` AS `tc` ON
            `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND
            `tc`.`TABLE_NAME` = `kcu`.`TABLE_NAME` AND
            `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME` AND
            `tc`.`CONSTRAINT_TYPE` IN ('PRIMARY KEY', 'UNIQUE')
        WHERE
            `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
            `kcu`.`TABLE_NAME` = :tableName
        ORDER BY `position` ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $constraints = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @var array<array-key, array> $constraints */
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            'primaryKey' => null,
            'foreignKeys' => [],
            'uniques' => [],
        ];

        /**
         * @var string $type
         * @var array $names
         */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PRIMARY KEY':
                        $ct = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));

                        $result['primaryKey'] = $ct;

                        break;
                    case 'FOREIGN KEY':
                        $fk = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate($constraint[0]['on_update'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);

                        $result['foreignKeys'][] = $fk;

                        break;
                    case 'UNIQUE':
                        $ct = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);

                        $result['uniques'][] = $ct;

                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws NotSupportedException
     *
     * @return array default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException('MySQL does not support default value constraints.');
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array foreign keys for the given table.
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        $tableForeignKeys = $this->loadTableConstraints($tableName, 'foreignKeys');

        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return IndexConstraint[] indexes for the given table.
     */
    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            `s`.`INDEX_NAME` AS `name`,
            `s`.`COLUMN_NAME` AS `column_name`,
            `s`.`NON_UNIQUE` ^ 1 AS `index_is_unique`,
            `s`.`INDEX_NAME` = 'PRIMARY' AS `index_is_primary`
        FROM `information_schema`.`STATISTICS` AS `s`
        WHERE
            `s`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
            `s`.`INDEX_SCHEMA` = `s`.`TABLE_SCHEMA` AND
            `s`.`TABLE_NAME` = :tableName
        ORDER BY `s`.`SEQ_IN_INDEX` ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @var array[] $indexes */
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');
        $result = [];

        /**
         * @psalm-var object|string|null $name
         * @psalm-var array[] $index
         */
        foreach ($indexes as $name => $index) {
            $ic = new IndexConstraint();

            $ic->primary((bool) $index[0]['index_is_primary']);
            $ic->unique((bool) $index[0]['index_is_unique']);
            $ic->name($name !== 'PRIMARY' ? $name : null);
            $ic->columnNames(ArrayHelper::getColumn($index, 'column_name'));

            $result[] = $ic;
        }

        return $result;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return Constraint|null primary key for the given table, `null` if the table has no primary key.*
     */
    protected function loadTablePrimaryKey(string $tableName): ?Constraint
    {
        $tablePrimaryKey = $this->loadTableConstraints($tableName, 'primaryKey');

        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @throws Exception|Throwable
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    protected function loadTableSchema(string $name): ?TableSchema
    {
        $table = new TableSchema();

        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        }

        return null;
    }

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array unique constraints for the given table.
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function loadTableUniques(string $tableName): array
    {
        $tableUniques = $this->loadTableConstraints($tableName, 'uniques');

        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * Changes row's array key case to lower if PDO's one is set to uppercase.
     *
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     *
     * @throws \Exception
     *
     * @return array normalized row or rows.
     */
    protected function normalizePdoRowKeyCase(array $row, bool $multiple): array
    {
        if ($this->db->getSlavePdo()?->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_UPPER) {
            return $row;
        }

        if ($multiple) {
            return array_map(static function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name the table name.
     *
     * @return TableSchema
     *
     * {@see TableSchema}
     */
    protected function resolveTableName(string $name): TableSchema
    {
        $resolvedName = new TableSchema();

        $parts = explode('.', str_replace('`', '', $name));

        if (isset($parts[1])) {
            $resolvedName->schemaName($parts[0]);
            $resolvedName->name($parts[1]);
        } else {
            $resolvedName->schemaName($this->defaultSchema);
            $resolvedName->name($name);
        }

        $resolvedName->fullName(($resolvedName->getSchemaName() !== $this->defaultSchema ?
            (string) $resolvedName->getSchemaName() . '.' : '') . $resolvedName->getName());

        return $resolvedName;
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableSchema $table the table metadata object.
     * @param string $name the table name.
     */
    protected function resolveTableNames(TableSchema $table, string $name): void
    {
        $parts = explode('.', str_replace('`', '', $name));

        if (isset($parts[1])) {
            $table->schemaName($parts[0]);
            $table->name($parts[1]);
            $table->fullName((string) $table->getSchemaName() . '.' . $table->getName());
        } else {
            $table->name($parts[0]);
            $table->fullName($parts[0]);
        }
    }

    /**
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     *
     * @return ColumnSchema column schema instance.
     */
    private function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
    }
}
