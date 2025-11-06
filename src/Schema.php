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
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_column;
use function array_map;
use function array_values;
use function in_array;
use function is_string;
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
 *   character_set_name: string|null,
 *   collation_name: string|null,
 *   column_type: string,
 *   column_key: string,
 *   extra: string,
 *   column_comment: string,
 *   schema: string,
 *   table: string
 * }
 * @psalm-type ForeignKeysArray = array<
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
    protected function findConstraints(TableSchemaInterface $table): void
    {
        $tableName = $this->resolveFullName($table->getName(), $table->getSchemaName());

        $table->foreignKeys(...$this->getTableMetadata($tableName, SchemaInterface::FOREIGN_KEYS));
        $table->indexes(...$this->getTableMetadata($tableName, SchemaInterface::INDEXES));
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
                `CHARACTER_SET_NAME`,
                `COLLATION_NAME`,
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
                ':schemaName' => $schemaName ?: null,
                ':tableName' => $tableName,
            ],
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

            if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
                $table->sequenceName('');
            }
        }

        return true;
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
            ':schemaName' => $tableSchema->getSchemaName() ?: null,
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
        $tableName = $table->getFullName();

        try {
            /** @psalm-var array<array-key, string> $row */
            $row = $this->db->createCommand(
                'SHOW CREATE TABLE ' . $this->db->getQuoter()->quoteTableName($tableName),
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
    protected function loadResultColumn(array $metadata): ?ColumnInterface
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

    protected function loadTableChecks(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
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
        WHERE
            `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND
            `kcu`.`TABLE_NAME` = :tableName
        ORDER BY `position` ASC
        SQL;

        $nameParts = $this->db->getQuoter()->getTableNameParts($tableName);
        $foreignKeys = $this->db->createCommand($sql, [
            ':schemaName' => $nameParts['schemaName'] ?? null,
            ':tableName' => $nameParts['name'],
        ])->queryAll();

        $foreignKeys = array_map(array_change_key_case(...), $foreignKeys);
        $foreignKeys = DbArrayHelper::arrange($foreignKeys, ['name']);

        $result = [];

        /**
         * @var string $name
         * @psalm-var ForeignKeysArray $foreignKey
         */
        foreach ($foreignKeys as $name => $foreignKey) {
            $result[$name] = new ForeignKey(
                $name,
                array_column($foreignKey, 'column_name'),
                $foreignKey[0]['foreign_table_schema'],
                $foreignKey[0]['foreign_table_name'],
                array_column($foreignKey, 'foreign_column_name'),
                $foreignKey[0]['on_delete'],
                $foreignKey[0]['on_update'],
            );
        }

        return $result;
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
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

        $nameParts = $this->db->getQuoter()->getTableNameParts($tableName);
        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $nameParts['schemaName'] ?? null,
            ':tableName' => $nameParts['name'],
        ])->queryAll();

        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::arrange($indexes, ['name']);
        $result = [];

        /**
         * @var string $name
         * @psalm-var list<array{name: string, column_name: string, is_unique: string, is_primary_key: string}> $index
         */
        foreach ($indexes as $name => $index) {
            $result[$name] = new Index(
                $name,
                array_column($index, 'column_name'),
                (bool) $index[0]['is_unique'],
                (bool) $index[0]['is_primary_key'],
            );
        }

        return $result;
    }

    protected function loadTableSchema(string $name): ?TableSchemaInterface
    {
        $table = new TableSchema(...$this->db->getQuoter()->getTableNameParts($name));
        $this->resolveTableCreateSql($table);

        if ($this->findColumns($table)) {
            $this->findTableComment($table);
            $this->findConstraints($table);

            return $table;
        }

        return null;
    }

    protected function resolveTableCreateSql(TableSchemaInterface $table): void
    {
        $sql = $this->getCreateTableSql($table);
        $table->createSql($sql);
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
            'characterSet' => $info['character_set_name'],
            'collation' => $info['collation_name'],
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

        /** @psalm-suppress InvalidArgument */
        $column = $this->db->getColumnFactory()->fromDefinition($info['column_type'], $columnInfo);

        if (str_starts_with($extra, 'DEFAULT_GENERATED')) {
            $extra = trim(substr($extra, 18));
            $column->extra($extra === '' ? null : $extra);
        }

        return $column;
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
