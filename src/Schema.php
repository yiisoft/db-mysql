<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_map;
use function array_values;
use function bindec;
use function in_array;
use function is_string;
use function ksort;
use function md5;
use function preg_match_all;
use function preg_match;
use function serialize;
use function stripos;
use function strtolower;
use function trim;

/**
 * Implements MySQL, MariaDB specific schema, supporting MySQL Server 5.7, MariaDB Server 10.4 and higher.
 *
 * @psalm-type ColumnInfoArray = array{
 *   field: string,
 *   type: string,
 *   collation: string|null,
 *   null: string,
 *   key: string,
 *   default: string|null,
 *   extra: string,
 *   extra_default_value: string|null,
 *   privileges: string,
 *   comment: string,
 *   enum_values?: string[],
 *   size?: int,
 *   precision?: int,
 *   scale?: int,
 * }
 * @psalm-type RowConstraint = array{
 *   constraint_name: string,
 *   column_name: string,
 *   referenced_table_name: string,
 *   referenced_column_name: string
 * }
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
final class Schema extends AbstractPdoSchema
{
    public function createColumn(string $type, array|int|string $length = null): ColumnInterface
    {
        return new Column($type, $length);
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
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $sql = $this->getCreateTableSql($table);
        $uniqueIndexes = [];
        $regexp = '/UNIQUE KEY\s+[`"](.+)[`"]\s*\(([`"].+[`"])+\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $indexName = $match[1];
                $indexColumns = array_map(trim(...), preg_split('/[`"],[`"]/', trim($match[2], '`"')));
                $uniqueIndexes[$indexName] = $indexColumns;
            }
        }

        ksort($uniqueIndexes);

        return $uniqueIndexes;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws Throwable If DB query fails.
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $tableName = $table->getFullName() ?? '';
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->db->getQuoter()->quoteTableName($tableName);

        try {
            $columns = $this->db->createCommand($sql)->queryAll();
            // Chapter 1: crutches for MariaDB. {@see https://github.com/yiisoft/yii2/issues/19747}
            $columnsExtra = [];
            if (str_contains($this->db->getServerVersion(), 'MariaDB')) {
                $rows = $this->db->createCommand(
                    <<<SQL
                    SELECT `COLUMN_NAME` as name,`COLUMN_DEFAULT` as default_value
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = COALESCE(:schemaName, DATABASE()) AND TABLE_NAME = :tableName
                    SQL ,
                    [
                        ':schemaName' => $table->getSchemaName(),
                        ':tableName' => $table->getName(),
                    ]
                )->queryAll();
                /** @psalm-var string[] $cols */
                foreach ($rows as $cols) {
                    $columnsExtra[$cols['name']] = $cols['default_value'];
                }
            }
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if ($previous && str_contains($previous->getMessage(), 'SQLSTATE[42S02')) {
                /**
                 * The table doesn't exist.
                 *
                 * @link https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                 */
                return false;
            }

            throw $e;
        }

        $jsonColumns = $this->getJsonColumns($table);

        /** @psalm-var ColumnInfoArray $info */
        foreach ($columns as $info) {
            /** @psalm-var ColumnInfoArray $info */
            $info = array_change_key_case($info);

            $info['extra_default_value'] = $columnsExtra[$info['field']] ?? '';

            if (in_array($info['field'], $jsonColumns, true)) {
                $info['type'] = ColumnType::JSON;
            }

            $column = $this->loadColumnSchema($info);
            $table->column($info['field'], $column);

            if ($column->isPrimaryKey()) {
                $table->primaryKey($info['field']);
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
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findConstraints(TableSchemaInterface $table): void
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
        WHERE `rc`.`CONSTRAINT_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND `rc`.`TABLE_NAME` = :tableName
        SQL;

        $constraints = [];
        $rows = $this->db->createCommand($sql, [
            ':schemaName' => $table->getSchemaName(),
            ':tableName' => $table->getName(),
        ])->queryAll();

        /**  @psalm-var RowConstraint $row */
        foreach ($rows as $row) {
            $constraints[$row['constraint_name']]['referenced_table_name'] = $row['referenced_table_name'];
            $constraints[$row['constraint_name']]['columns'][$row['column_name']] = $row['referenced_column_name'];
        }

        $table->foreignKeys([]);

        /**
         * @psalm-var array{referenced_table_name: string, columns: array} $constraint
         */
        foreach ($constraints as $name => $constraint) {
            $table->foreignKey($name, [$constraint['referenced_table_name'], ...$constraint['columns']]);
        }
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT schema_name FROM information_schema.schemata WHERE schema_name NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findTableComment(TableSchemaInterface $tableSchema): void
    {
        $sql = <<<SQL
        SELECT `TABLE_COMMENT`
        FROM `INFORMATION_SCHEMA`.`TABLES`
        WHERE
              `TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
              `TABLE_NAME` = :tableName;
        SQL;

        $comment = $this->db->createCommand($sql, [
            ':schemaName' => $tableSchema->getSchemaName(),
            ':tableName' => $tableSchema->getName(),
        ])->queryScalar();

        $tableSchema->comment(is_string($comment) ? $comment : null);
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes to support this feature because the default implementation
     * simply throws an exception.
     *
     * @param string $schema The schema of the tables.
     * Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All tables name in the database. The names have NO schema name prefix.
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findViewNames(string $schema = ''): array
    {
        $sql = match ($schema) {
            '' => <<<SQL
            SELECT table_name as view FROM information_schema.tables WHERE table_type LIKE 'VIEW' AND table_schema != 'sys' order by table_name
            SQL,
            default => <<<SQL
            SELECT table_name as view FROM information_schema.tables WHERE table_type LIKE 'VIEW' AND table_schema = '$schema' order by table_name
            SQL,
        };

        /** @psalm-var string[][] $views */
        $views = $this->db->createCommand($sql)->queryAll();

        foreach ($views as $key => $view) {
            $views[$key] = $view['view'];
        }

        return $views;
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name The table name.
     *
     * @return array The cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [self::class, ...$this->generateCacheKey(), $this->db->getQuoter()->getRawTableName($name)];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string The cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([self::class, ...$this->generateCacheKey()]));
    }

    /**
     * Gets the `CREATE TABLE` SQL string.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return string $sql The result of `SHOW CREATE TABLE`.
     */
    protected function getCreateTableSql(TableSchemaInterface $table): string
    {
        $tableName = $table->getFullName() ?? '';

        try {
            /** @psalm-var array<array-key, string> $row */
            $row = $this->db->createCommand(
                'SHOW CREATE TABLE ' . $this->db->getQuoter()->quoteTableName($tableName)
            )->queryOne();

            if (isset($row['Create Table'])) {
                $sql = $row['Create Table'];
            } else {
                $row = array_values($row);
                $sql = $row[1];
            }
        } catch (Exception) {
            $sql = '';
        }

        return $sql;
    }

    /**
     * Loads the column information into a {@see ColumnSchemaInterface} object.
     *
     * @param array $info The column information.
     *
     * @return ColumnSchemaInterface The column schema object.
     *
     * @psalm-param ColumnInfoArray $info The column information.
     */
    private function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $columnFactory = $this->db->getColumnFactory();

        $dbType = $info['type'];
        /** @psalm-var ColumnInfoArray $info */
        $column = $columnFactory->fromDefinition($dbType);
        $column->name($info['field']);
        $column->allowNull($info['null'] === 'YES');
        $column->primaryKey(str_contains($info['key'], 'PRI'));
        $column->autoIncrement(stripos($info['extra'], 'auto_increment') !== false);
        $column->comment($info['comment']);
        $column->dbType($dbType);

        // Chapter 2: crutches for MariaDB {@see https://github.com/yiisoft/yii2/issues/19747}
        $extra = $info['extra'];
        if (
            empty($extra)
            && !empty($info['extra_default_value'])
            && !str_starts_with($info['extra_default_value'], '\'')
            && in_array($column->getType(), [
                ColumnType::CHAR, ColumnType::STRING, ColumnType::TEXT,
                ColumnType::DATETIME, ColumnType::TIMESTAMP, ColumnType::TIME, ColumnType::DATE,
            ], true)
        ) {
            $extra = 'DEFAULT_GENERATED';
        }

        $column->extra($extra);
        $column->defaultValue($this->normalizeDefaultValue($info['default'], $column));

        if (str_starts_with($extra, 'DEFAULT_GENERATED')) {
            $column->extra(trim(strtoupper(substr($extra, 18))));
        }

        return $column;
    }

    /**
     * Converts column's default value according to {@see ColumnSchema::phpType} after retrieval from the database.
     *
     * @param string|null $defaultValue The default value retrieved from the database.
     * @param ColumnSchemaInterface $column The column schema object.
     *
     * @return mixed The normalized default value.
     */
    private function normalizeDefaultValue(?string $defaultValue, ColumnSchemaInterface $column): mixed
    {
        if ($defaultValue === null) {
            return null;
        }

        if ($column->isPrimaryKey()) {
            return $column->phpTypecast($defaultValue);
        }

        if (
            in_array($column->getType(), [ColumnType::TIMESTAMP, ColumnType::DATETIME, ColumnType::DATE, ColumnType::TIME], true)
            && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', $defaultValue, $matches) === 1
        ) {
            return new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1]) ? '(' . $matches[1] . ')' : ''));
        }

        if (!empty($defaultValue) && !empty($column->getExtra())) {
            return new Expression($defaultValue);
        }

        if (str_starts_with(strtolower((string) $column->getDbType()), 'bit')) {
            return $column->phpTypecast(bindec(trim($defaultValue, "b'")));
        }

        return $column->phpTypecast($defaultValue);
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws NotSupportedException
     *
     * @return array Check constraints for the given table.
     */
    protected function loadTableChecks(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @psalm-return Constraint[]|ForeignKeyConstraint[]|Constraint|null
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

        /** @psalm-var array[][] $constraints */
        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
        ];

        /**
         * @psalm-var string $type
         * @psalm-var array $names
         */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PRIMARY KEY':
                        $result[self::PRIMARY_KEY] = (new Constraint())
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'FOREIGN KEY':
                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(DbArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate($constraint[0]['on_update'])
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'UNIQUE':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->columnNames(DbArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
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
     * @param string $tableName The table name.
     *
     * @throws NotSupportedException
     *
     * @return array Default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Foreign keys for the given table.
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        $tableForeignKeys = $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return IndexConstraint[] Indexes for the given table.
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

        /** @psalm-var array[] $indexes */
        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::index($indexes, null, ['name']);
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
            $ic->columnNames(DbArrayHelper::getColumn($index, 'column_name'));

            $result[] = $ic;
        }

        return $result;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return Constraint|null Primary key for the given table, `null` if the table has no primary key.*
     */
    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name The table name.
     *
     * @throws Exception
     * @throws Throwable
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table doesn't exist.
     */
    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $table = $this->resolveTableName($name);
        $this->resolveTableCreateSql($table);
        $this->findTableComment($table);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        }

        return null;
    }

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Unique constraints for the given table.
     */
    protected function loadTableUniques(string $tableName): array
    {
        $tableUniques = $this->loadTableConstraints($tableName, self::UNIQUES);
        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name The table name.
     *
     * @see TableSchemaInterface
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        $resolvedName = new TableSchema();

        $parts = array_reverse($this->db->getQuoter()->getTableNameParts($name));
        $resolvedName->name($parts[0] ?? '');
        $resolvedName->schemaName($parts[1] ?? $this->defaultSchema);
        $resolvedName->fullName(
            $resolvedName->getSchemaName() !== $this->defaultSchema ?
            implode('.', array_reverse($parts)) : $resolvedName->getName()
        );

        return $resolvedName;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function resolveTableCreateSql(TableSchemaInterface $table): void
    {
        $sql = $this->getCreateTableSql($table);
        $table->createSql($sql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function getJsonColumns(TableSchemaInterface $table): array
    {
        $sql = $this->getCreateTableSql($table);
        $result = [];
        $regexp = '/json_valid\([\`"](.+)[\`"]\s*\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $result[] = $match[1];
            }
        }

        return $result;
    }
}
