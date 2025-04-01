<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_column;
use function array_map;
use function array_values;
use function in_array;
use function is_string;
use function ksort;
use function preg_match_all;
use function str_contains;
use function str_ireplace;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Implements MySQL, MariaDB specific schema, supporting MySQL Server 5.7, MariaDB Server 10.4 and higher.
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   column_default: string|null,
 *   is_nullable: string,
 *   column_type: string,
 *   column_key: string,
 *   extra: string,
 *   column_comment: string,
 *   schema: string,
 *   table: string
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
 *     on_update: ReferentialAction::*,
 *     on_delete: ReferentialAction::*,
 *     check_expr: string
 *   }
 * >
 */
final class Schema extends AbstractPdoSchema
{
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
                /** @var string[] $indexColumns */
                $indexColumns = preg_split('/[`"],[`"]/', trim($match[2], '`"'));
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
        $schemaName = $table->getSchemaName();
        $tableName = $table->getName();

        $columns = $this->db->createCommand(
            <<<SQL
            SELECT
                `COLUMN_NAME`,
                `COLUMN_DEFAULT`,
                `IS_NULLABLE`,
                `COLUMN_TYPE`,
                `COLUMN_KEY`,
                `EXTRA`,
                `COLUMN_COMMENT`
            FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE())
                AND `TABLE_NAME` = :tableName
            ORDER BY `ORDINAL_POSITION`
            SQL,
            [
                ':schemaName' => $schemaName,
                ':tableName' => $tableName,
            ]
        )->queryAll();

        if (empty($columns)) {
            return false;
        }

        $jsonColumns = $this->getJsonColumns($table);
        $isMariaDb = str_contains($this->db->getServerInfo()->getVersion(), 'MariaDB');

        foreach ($columns as $info) {
            $info = array_change_key_case($info);

            $info['schema'] = $schemaName;
            $info['table'] = $tableName;

            if (in_array($info['column_name'], $jsonColumns, true)) {
                $info['column_type'] = ColumnType::JSON;
            }

            if ($isMariaDb && $info['column_default'] === 'NULL') {
                $info['column_default'] = null;
            }

            /** @psalm-var ColumnArray $info */
            $column = $this->loadColumn($info);
            $table->column($info['column_name'], $column);

            if ($column->isPrimaryKey()) {
                $table->primaryKey($info['column_name']);
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
     * Loads the column information into a {@see ColumnInterface} object.
     *
     * @param array $info The column information.
     *
     * @return ColumnInterface The column object.
     *
     * @psalm-param ColumnArray $info The column information.
     */
    private function loadColumn(array $info): ColumnInterface
    {
        $extra = trim(str_ireplace('auto_increment', '', $info['extra'], $autoIncrement));

        $column = $this->db->getColumnFactory()->fromDefinition($info['column_type'], [
            'autoIncrement' => $autoIncrement > 0,
            'comment' => $info['column_comment'] === '' ? null : $info['column_comment'],
            'defaultValueRaw' => $info['column_default'],
            'extra' => $extra === '' ? null : $extra,
            'name' => $info['column_name'],
            'notNull' => $info['is_nullable'] !== 'YES',
            'primaryKey' => $info['column_key'] === 'PRI',
            'schema' => $info['schema'],
            'table' => $info['table'],
            'unique' => $info['column_key'] === 'UNI',
        ]);

        if (str_starts_with($extra, 'DEFAULT_GENERATED')) {
            $column->extra(trim(substr($extra, 18)));
        }

        return $column;
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
                            ->columnNames(array_column($constraint, 'column_name'));
                        break;
                    case 'FOREIGN KEY':
                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(array_column($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate($constraint[0]['on_update'])
                            ->columnNames(array_column($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'UNIQUE':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->columnNames(array_column($constraint, 'column_name'))
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
            $ic->columnNames(array_column($index, 'column_name'));

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
        $regexp = '/json_valid\([`"](.+)[`"]\s*\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $result[] = $match[1];
            }
        }

        return $result;
    }
}
