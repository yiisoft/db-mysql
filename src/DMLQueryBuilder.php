<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;
use Yiisoft\Db\Schema\TableSchema;

use function array_combine;
use function array_diff;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function is_array;
use function str_starts_with;
use function substr;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for MySQL, MariaDB.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    /** @throws NotSupportedException */
    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        $sequenceName = $tableSchema->getSequenceName();
        if ($sequenceName === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$table'.");
        }

        $tableName = $this->quoter->quoteTableName($table);

        if ($value !== null) {
            return 'ALTER TABLE ' . $tableName . ' AUTO_INCREMENT=' . (string) $value . ';';
        }

        $key = $tableSchema->getPrimaryKey()[0];

        return "SET @new_autoincrement_value := (SELECT MAX(`$key`) + 1 FROM $tableName);
SET @sql = CONCAT('ALTER TABLE $tableName AUTO_INCREMENT =', @new_autoincrement_value);
PREPARE autoincrement_stmt FROM @sql;
EXECUTE autoincrement_stmt";
    }

    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression('EXCLUDED.' . $this->quoter->quoteSimpleColumnName($name));
            }
        }

        if (empty($updateColumns)) {
            $insertSql = $this->insert($table, $insertColumns, $params);
            return 'INSERT IGNORE' . substr($insertSql, 6);
        }

        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        $quotedNames = array_map($this->quoter->quoteColumnName(...), $names);

        if (!empty($placeholders)) {
            $selectValues = [];

            foreach ($placeholders as $i => $placeholder) {
                $selectValues[] = "$placeholder AS $quotedNames[$i]";
            }

            $values = 'SELECT ' . implode(', ', $selectValues);
        }

        $fields = implode(', ', $quotedNames);

        $insertSql = 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . " ($fields) SELECT $fields FROM ($values) AS EXCLUDED";

        $updates = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        $tableSchema = $this->schema->getTableSchema($table);
        $returnColumns ??= $tableSchema?->getColumnNames();

        $upsertSql = $this->upsert($table, $insertColumns, $updateColumns, $params);

        if (empty($returnColumns)) {
            return $upsertSql;
        }

        $quoter = $this->quoter;
        [$uniqueNames, $insertNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);
        /** @var TableSchema $tableSchema */
        $primaryKeys = $tableSchema->getPrimaryKey();
        $uniqueColumns = $primaryKeys ?: $uniqueNames;

        if (is_array($insertColumns)) {
            $insertColumns = array_combine($insertNames, $insertColumns);
        }


        if (empty($uniqueColumns)) {
            $returnValues = $this->prepareColumnValues($tableSchema, $returnColumns, $insertColumns, $params);
            $selectValues = [];

            foreach ($returnValues as $name => $value) {
                $selectValues[] = $value . ' AS ' . $quoter->quoteSimpleColumnName($name);
            }

            return $upsertSql . ';SELECT ' . implode(', ', $selectValues);
        }

        if (is_array($updateColumns) && !empty(array_intersect($uniqueColumns, array_keys($updateColumns)))) {
            throw new NotSupportedException(
                __METHOD__ . '() is not supported by MySQL when updating primary key or unique values.'
            );
        }

        $quotedTable = $quoter->quoteTableName($table);
        $isAutoIncrement = count($primaryKeys) === 1 && $tableSchema->getColumn($primaryKeys[0])?->isAutoIncrement();

        if ($isAutoIncrement) {
            $id = $quoter->quoteSimpleColumnName($primaryKeys[0]);
            $setLastInsertId = "$id=LAST_INSERT_ID($quotedTable.$id)";

            if (str_starts_with($upsertSql, 'INSERT IGNORE INTO')) {
                $upsertSql = 'INSERT' . substr($upsertSql, 13) . " ON DUPLICATE KEY UPDATE $setLastInsertId";
            } elseif (str_contains($upsertSql, ' ON DUPLICATE KEY UPDATE ')) {
                $upsertSql .= ", $setLastInsertId";
            }
        }

        $uniqueValues = $this->prepareColumnValues($tableSchema, $uniqueColumns, $insertColumns, $params);

        if (empty(array_diff($returnColumns, array_keys($uniqueValues)))) {
            $selectValues = [];

            foreach ($returnColumns as $name) {
                $selectValues[] = $uniqueValues[$name] . ' AS ' . $quoter->quoteSimpleColumnName($name);
            }

            return $upsertSql . ';SELECT ' . implode(', ', $selectValues);
        }

        $conditions = [];

        foreach ($uniqueValues as $name => $value) {
            if (array_key_exists($value, $params) && $params[$value] === null) {
                throw new NotSupportedException(
                    __METHOD__ . '() is not supported by MySQL when inserting `null` primary key or unique values.'
                );
            }

            $conditions[] = $quoter->quoteSimpleColumnName($name) . ' = ' . $value;
        }

        $quotedReturnColumns = array_map($quoter->quoteSimpleColumnName(...), $returnColumns);

        return $upsertSql
            . ';SELECT ' . implode(', ', $quotedReturnColumns)
            . ' FROM ' . $quotedTable
            . ' WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * @param string[] $columnNames
     *
     * @return string[] Prepared column values for using in a SQL statement.
     * @psalm-return array<string, string>
     */
    private function prepareColumnValues(
        TableSchema $tableSchema,
        array $columnNames,
        array|QueryInterface $insertColumns,
        array &$params,
    ): array {
        $columnValues = [];

        $tableColumns = $tableSchema->getColumns();

        foreach ($columnNames as $name) {
            $column = $tableColumns[$name];

            if ($column->isAutoIncrement()) {
                $columnValues[$name] = 'LAST_INSERT_ID()';
            } elseif ($insertColumns instanceof QueryInterface) {
                throw new NotSupportedException(
                    self::class . '::upsertReturning() is not supported by MySQL'
                    . ' for tables without auto increment when inserting sub-query.'
                );
            } else {
                $value = $insertColumns[$name] ?? $column->getDefaultValue();

                if ($value instanceof ExpressionInterface) {
                    $columnValues[$name] = $this->queryBuilder->buildExpression($value, $params);
                } else {
                    $columnValues[$name] = $this->queryBuilder->bindParam($value, $params);
                }
            }
        }

        return $columnValues;
    }

    protected function prepareInsertValues(string $table, array|QueryInterface $columns, array $params = []): array
    {
        if (empty($columns)) {
            return [[], [], 'VALUES ()', []];
        }

        return parent::prepareInsertValues($table, $columns, $params);
    }
}
