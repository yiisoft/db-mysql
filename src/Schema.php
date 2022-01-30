<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use JsonException;
use PDO;
use PDOException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Schema as AbstractSchema;

use function array_change_key_case;
use function array_map;
use function array_merge;
use function array_values;
use function bindec;
use function explode;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function stripos;
use function strpos;
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
final class Schema extends AbstractSchema
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

    /**
     * @var string|string[] character used to quote schema, table, etc. names. An array of 2 characters can be used in
     * case starting and ending characters are different.
     */
    protected $tableQuoteCharacter = '`';

    /**
     * @var string|string[] character used to quote column names. An array of 2 characters can be used in case starting
     * and ending characters are different.
     */
    protected $columnQuoteCharacter = '`';

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
            (string) $resolvedName->getSchemaName() . '.' : '') . (string) $resolvedName->getName());

        return $resolvedName;
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
            $sql .= ' FROM ' . $this->quoteSimpleTableName($schema);
        }

        return $this->getDb()->createCommand($sql)->queryColumn();
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
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array|ForeignKeyConstraint[] foreign keys for the given table.
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
        $sql = <<<'SQL'
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

        $indexes = $this->getDb()->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @var array<array-key, array<array-key, mixed>> $indexes */
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');
        $result = [];

        /**
         * @psalm-var object|string|null $name
         * @psalm-var array<array-key, array<array-key, mixed>> $index
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
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array|Constraint[] unique constraints for the given table.
     */
    protected function loadTableUniques(string $tableName): array
    {
        $tableUniques = $this->loadTableConstraints($tableName, 'uniques');

        return is_array($tableUniques) ? $tableUniques : [];
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
     * Creates a query builder for the MySQL database.
     *
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->getDb());
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
            $table->fullName((string) $table->getSchemaName() . '.' . (string) $table->getName());
        } else {
            $table->name($parts[0]);
            $table->fullName($parts[0]);
        }
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
        $column->primaryKey(strpos($info['key'], 'PRI') !== false);
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

        $sql = 'SHOW FULL COLUMNS FROM ' . $this->quoteTableName($tableName);

        try {
            $columns = $this->getDb()->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof PDOException && strpos($previous->getMessage(), 'SQLSTATE[42S02') !== false) {
                /**
                 * table does not exist.
                 *
                 * https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                 */
                return false;
            }

            throw $e;
        }

        $slavePdo = $this->getDb()->getSlavePdo();

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
        $row = $this->getDb()->createCommand(
            'SHOW CREATE TABLE ' . $this->quoteTableName($tableName)
        )->queryOne();

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        return $sql;
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
        $sql = <<<'SQL'
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
            $rows = $this->getDb()->createCommand($sql, [
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

            if (!$previous instanceof PDOException || strpos($previous->getMessage(), 'SQLSTATE[42S02') === false) {
                throw $e;
            }

            // table does not exist, try to determine the foreign keys using the table creation sql
            $sql = $this->getCreateTableSql($table);
            $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';

            if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fks = array_map('trim', explode(',', str_replace('`', '', $match[1])));
                    $pks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                    $constraint = [str_replace('`', '', $match[2])];

                    foreach ($fks as $k => $name) {
                        $constraint[$name] = $pks[$k];
                    }

                    $table->foreignKey(\md5(\serialize($constraint)), $constraint);
                }
                $table->foreignKeys(array_values($table->getForeignKeys()));
            }
        }
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

        $regexp = '/UNIQUE KEY\s+\`(.+)\`\s*\((\`.+\`)+\)/mi';

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
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string $length length or precision of the column. See {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     */
    public function createColumnSchemaBuilder(string $type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length, $this->getDb());
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
     * @return (Constraint|ForeignKeyConstraint)[]|Constraint|null constraints.
     *
     * @psalm-return Constraint|list<Constraint|ForeignKeyConstraint>|null
     */
    private function loadTableConstraints(string $tableName, string $returnType)
    {
        $sql = <<<'SQL'
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

        $constraints = $this->getDb()->createCommand($sql, [
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
