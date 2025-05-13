<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function implode;
use function str_replace;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for MySQL, MariaDB.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    /** @throws NotSupportedException */
    public function insertWithReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Mysql.');
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
        $insertSql = $this->insert($table, $insertColumns, $params);

        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $quotedName) {
                $updateColumns[$quotedName] = new Expression('VALUES(' . $quotedName . ')');
            }
        }

        if (empty($updateColumns)) {
            return str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insertSql);
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    /** @throws NotSupportedException */
    public function upsertWithReturningPks(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . '() is not supported by Mysql.');
    }

    protected function prepareInsertValues(string $table, array|QueryInterface $columns, array $params = []): array
    {
        if (empty($columns)) {
            return [[], [], 'VALUES ()', []];
        }

        return parent::prepareInsertValues($table, $columns, $params);
    }
}
