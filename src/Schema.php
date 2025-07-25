<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\Exception;
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
use function strtolower;
use function substr;
use function substr_compare;
use function trim;

use const PHP_INT_SIZE;

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
 *     foreign_table_schema: string,
 *     foreign_table_name: string,
 *     foreign_column_name: string,
 *     on_update: ReferentialAction::*,
 *     on_delete: ReferentialAction::*,
 *     check_expr: string
 *   }
 * >
 */
final class Schema extends AbstractPdoSchema
{
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

    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT schema_name FROM information_schema.schemata WHERE schema_name NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
        SQL;

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }

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

    protected function findTableNames(string $schema = ''): array
    {
        $sql = 'SHOW TABLES';

        if ($schema !== '') {
            $sql .= ' FROM ' . $this->db->getQuoter()->quoteSimpleTableName($schema);
        }

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }

    protected function findViewNames(string $schema = ''): array
    {
        $sql = match ($schema) {
            '' => <<<SQL
            SELECT table_name FROM information_schema.tables WHERE table_type = 'VIEW' AND table_schema != 'sys' order by table_name
            SQL,
            default => <<<SQL
            SELECT table_name FROM information_schema.tables WHERE table_type = 'VIEW' AND table_schema = '$schema' order by table_name
            SQL,
        };

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * Gets the `CREATE TABLE` SQL string.
     *
     * @param TableSchemaInterface $table The table metadata.
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
     * @psalm-param array{
     *     native_type: string,
     *     pdo_type: int,
     *     flags: string[],
     *     table: string,
     *     name: string,
     *     len: int,
     *     precision: int,
     * } $metadata
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function loadResultColumn(array $metadata): ColumnInterface|null
    {
        if (empty($metadata['native_type']) || $metadata['native_type'] === 'NULL') {
            return null;
        }

        $dbType = match ($metadata['native_type']) {
            'TINY' => 'tinyint',
            'SHORT' => 'smallint',
            'INT24' => 'mediumint',
            'LONG' => 'int',
            'LONGLONG' => $metadata['len'] < 10 ? 'int' : 'bigint',
            'NEWDECIMAL' => 'decimal',
            'STRING' => 'char',
            'VAR_STRING' => 'varchar',
            'BLOB' => match ($metadata['len']) {
                255 => 'tinyblob',
                510, 765, 1020 => 'tinytext',
                // 65535 => 'blob',
                131070, 196605, 262140 => 'text',
                16777215 => 'mediumblob',
                33554430, 50331645, 67108860 => 'mediumtext',
                4294967295 => 'longblob',
                default => 'blob',
            },
            default => strtolower($metadata['native_type']),
        };

        $columnInfo = [];

        if (!empty($metadata['table'])) {
            $columnInfo['table'] = $metadata['table'];
            $columnInfo['name'] = $metadata['name'];
        } elseif (!empty($metadata['name'])) {
            $columnInfo['name'] = $metadata['name'];
        }

        if (!empty($metadata['len'])) {
            $columnInfo['size'] = match ($dbType) {
                'decimal' => $metadata['len'] - ($metadata['precision'] === 0 ? 1 : 2),
                'time', 'datetime', 'timestamp' => $metadata['precision'],
                default => $metadata['len'],
            };
        }

        match ($dbType) {
            'float', 'double', 'decimal' => $columnInfo['scale'] = $metadata['precision'],
            'bigint' => $metadata['len'] === 20 ? $columnInfo['unsigned'] = true : null,
            'int' => $metadata['len'] === 10 && PHP_INT_SIZE !== 8 ? $columnInfo['unsigned'] = true : null,
            'timestamp' => $columnInfo['dbTimezone'] = $this->db->getServerInfo()->getTimezone(),
            default => null,
        };

        $columnInfo['notNull'] = in_array('not_null', $metadata['flags'], true);

        return $this->db->getColumnFactory()->fromDbType($dbType, $columnInfo);
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
        $columnInfo = [
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
        ];

        if (substr_compare($info['column_type'], 'timestamp', 0, 9, true) === 0) {
            $columnInfo['dbTimezone'] = $this->db->getServerInfo()->getTimezone();
        }

        $column = $this->db->getColumnFactory()->fromDefinition($info['column_type'], $columnInfo);

        if (str_starts_with($extra, 'DEFAULT_GENERATED')) {
            $extra = trim(substr($extra, 18));
            $column->extra($extra === '' ? null : $extra);
        }

        return $column;
    }

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
     * @psalm-return ForeignKey[]|Index[]|Index|null
     */
    private function loadTableConstraints(string $tableName, string $returnType): array|Index|null
    {
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `tc`.`CONSTRAINT_TYPE` AS `type`,
        CASE
            WHEN :schemaName IS NULL AND `kcu`.`REFERENCED_TABLE_SCHEMA` = DATABASE() THEN ''
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

        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::arrange($constraints, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
        ];

        /**
         * @var string $type
         * @psalm-var array<string, ConstraintArray> $names
         */
        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                match ($type) {
                    'PRIMARY KEY' => $result[self::PRIMARY_KEY] = new Index(
                        '',
                        array_column($constraint, 'column_name'),
                        true,
                        true,
                    ),
                    'FOREIGN KEY' => $result[self::FOREIGN_KEYS][] = new ForeignKey(
                        $name,
                        array_column($constraint, 'column_name'),
                        $constraint[0]['foreign_table_schema'],
                        $constraint[0]['foreign_table_name'],
                        array_column($constraint, 'foreign_column_name'),
                        $constraint[0]['on_delete'],
                        $constraint[0]['on_update'],
                    ),
                    'UNIQUE' => $result[self::UNIQUES][] = new Index(
                        $name,
                        array_column($constraint, 'column_name'),
                        true,
                    ),
                };
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @var ForeignKey[] */
        return $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            `s`.`INDEX_NAME` AS `name`,
            `s`.`COLUMN_NAME` AS `column_name`,
            `s`.`NON_UNIQUE` ^ 1 AS `is_unique`,
            `s`.`INDEX_NAME` = 'PRIMARY' AS `is_primary_key`
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

        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::arrange($indexes, ['name']);
        $result = [];

        /**
         * @var string $name
         * @psalm-var list<array{name: string, column_name: string, is_unique: string, is_primary_key: string}> $index
         */
        foreach ($indexes as $name => $index) {
            $isPrimaryKey = (bool) $index[0]['is_primary_key'];

            $result[] = new Index(
                $isPrimaryKey ? '' : $name,
                array_column($index, 'column_name'),
                (bool) $index[0]['is_unique'],
                $isPrimaryKey,
            );
        }

        return $result;
    }

    protected function loadTablePrimaryKey(string $tableName): Index|null
    {
        /** @var Index|null */
        return $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
    }

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

    protected function loadTableUniques(string $tableName): array
    {
        /** @var Index[] */
        return $this->loadTableConstraints($tableName, self::UNIQUES);
    }

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

    protected function resolveTableCreateSql(TableSchemaInterface $table): void
    {
        $sql = $this->getCreateTableSql($table);
        $table->createSql($sql);
    }

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
