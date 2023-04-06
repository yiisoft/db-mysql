<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use JsonException;
use Throwable;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\PdoAbstractSchema;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Schema\Builder\AbstractColumn;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_map;
use function array_merge;
use function array_values;
use function bindec;
use function explode;
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
 *   comment: string
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
final class Schema extends PdoAbstractSchema
{
    /**
     * @var array Mapping from physical column types (keys) to abstract column types (values).
     *
     * @psalm-var string[]
     */
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

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = $match[1];
                $indexColumns = array_map('trim', preg_split('/[`"],[`"]/', trim($match[2], '`"')));
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
                /** @psalm-var array[] $columnsExtra */
                $columnsExtra = $this->db->createCommand(
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
                foreach ($columnsExtra as $cols) {
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
            $info = $this->normalizeRowKeyCase($info, false);

            $info['extra_default_value'] = $columnsExtra[(string) $info['field']] ?? '';

            if (in_array($info['field'], $jsonColumns, true)) {
                $info['type'] = self::TYPE_JSON;
            }

            /** @psalm-var ColumnInfoArray $info */
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
            $table->foreignKey(
                $name,
                array_merge(
                    [$constraint['referenced_table_name']],
                    $constraint['columns']
                ),
            );
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
        return array_merge([self::class], $this->generateCacheKey(), [$this->getRawTableName($name)]);
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
        return md5(serialize(array_merge([self::class], $this->generateCacheKey())));
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
     * @throws JsonException
     *
     * @return ColumnSchemaInterface The column schema object.
     *
     * @psalm-param ColumnInfoArray $info The column information.
     */
    protected function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $dbType = $info['type'] ?? '';

        $column = $this->createColumnSchema($info['field']);

        /** @psalm-var ColumnInfoArray $info */
        $column->allowNull($info['null'] === 'YES');
        $column->primaryKey(str_contains($info['key'], 'PRI'));
        $column->autoIncrement(stripos($info['extra'], 'auto_increment') !== false);
        $column->comment($info['comment']);
        $column->dbType($dbType);
        $column->unsigned(stripos($dbType, 'unsigned') !== false);
        $column->type(self::TYPE_STRING);

        $extra = $info['extra'];

        if (str_starts_with($extra, 'DEFAULT_GENERATED')) {
            $extra = strtoupper(substr($extra, 18));
        }
        $column->extra(trim($extra));

        if (preg_match('/^(\w+)(?:\(([^)]+)\))?/', $dbType, $matches)) {
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

                    if ($type === 'bit') {
                        if ($column->getSize() === 1) {
                            $column->type(self::TYPE_BOOLEAN);
                        } elseif ($column->getSize() > 32) {
                            $column->type(self::TYPE_BIGINT);
                        } elseif ($column->getSize() === 32) {
                            $column->type(self::TYPE_INTEGER);
                        }
                    }
                }
            }
        }

        $column->phpType($this->getColumnPhpType($column));

        if (!$column->isPrimaryKey()) {
            // Chapter 2: crutches for MariaDB {@see https://github.com/yiisoft/yii2/issues/19747}
            /** @psalm-var string $columnCategory */
            $columnCategory = $this->createColumn(
                $column->getType(),
                $column->getSize()
            )->getCategoryMap()[$column->getType()] ?? '';
            $defaultValue = $info['extra_default_value'] ?? '';

            if (
                empty($info['extra']) &&
                !empty($defaultValue) &&
                in_array($columnCategory, [
                    AbstractColumn::TYPE_CATEGORY_STRING,
                    AbstractColumn::TYPE_CATEGORY_TIME,
                ], true)
                && !str_starts_with($defaultValue, '\'')
            ) {
                $info['extra'] = 'DEFAULT_GENERATED';
            }

            /**
             * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
             * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
             *
             * See details here: https://mariadb.com/kb/en/library/now/#description
             */
            if (
                in_array($column->getType(), [self::TYPE_TIMESTAMP, self::TYPE_DATETIME, self::TYPE_DATE, self::TYPE_TIME], true)
                && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', (string) $info['default'], $matches)
            ) {
                $column->defaultValue(new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1])
                    ? '(' . $matches[1] . ')' : '')));
            } elseif (!empty($info['extra']) && !empty($info['default'])) {
                $column->defaultValue(new Expression($info['default']));
            } elseif (isset($type) && $type === 'bit' && $column->getType() !== self::TYPE_BOOLEAN) {
                $column->defaultValue(bindec(trim((string) $info['default'], 'b\'')));
            } else {
                $column->defaultValue($column->phpTypecast($info['default']));
            }
        } elseif ($info['default'] !== null) {
            $column->defaultValue($column->phpTypecast($info['default']));
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
        $constraints = $this->normalizeRowKeyCase($constraints, true);
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
        $indexes = $this->normalizeRowKeyCase($indexes, true);
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
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     *
     * @param string $name Name of the column.
     *
     * @return ColumnSchema
     */
    private function createColumnSchema(string $name): ColumnSchema
    {
        return new ColumnSchema($name);
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

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result[] = $match[1];
            }
        }

        return $result;
    }
}
