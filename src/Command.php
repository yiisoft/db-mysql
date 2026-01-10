<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use PDOStatement;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;

use function in_array;
use function restore_error_handler;
use function set_error_handler;
use function str_starts_with;
use function substr;

use const E_WARNING;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for MySQL,
 * MariaDB.
 */
final class Command extends AbstractPdoCommand
{
    public function insertReturningPks(string $table, array|QueryInterface $columns): array
    {
        $tableSchema = $this->db->getSchema()->getTableSchema($table);
        $primaryKeys = $tableSchema?->getPrimaryKey() ?? [];
        $tableColumns = $tableSchema?->getColumns() ?? [];

        foreach ($primaryKeys as $name) {
            $column = $tableColumns[$name];

            if ($column->isAutoIncrement()) {
                continue;
            }

            if ($columns instanceof QueryInterface) {
                throw new NotSupportedException(
                    __METHOD__ . '() is not supported by MySQL for tables without auto increment when inserting sub-query.',
                );
            }

            break;
        }

        $params = [];
        $insertSql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $this->setSql($insertSql)->bindValues($params);

        $this->execute();

        if (empty($primaryKeys)) {
            return [];
        }

        $result = [];

        foreach ($primaryKeys as $name) {
            $column = $tableColumns[$name];

            if ($column->isAutoIncrement()) {
                $value = $this->db->getLastInsertId();
            } else {
                /** @var array $columns */
                $value = $columns[$name] ?? $column->getDefaultValue();
            }

            if ($this->phpTypecasting) {
                $value = $column->phpTypecast($value);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        ?array $returnColumns = null,
    ): array {
        $returnColumns ??= $this->db->getTableSchema($table)?->getColumnNames();

        if (empty($returnColumns)) {
            $this->upsert($table, $insertColumns, $updateColumns)->execute();
            return [];
        }

        $params = [];
        $sql = $this->getQueryBuilder()
            ->upsertReturning($table, $insertColumns, $updateColumns, $returnColumns, $params);

        $this->setSql($sql)->bindValues($params);
        $this->queryInternal(self::QUERY_MODE_EXECUTE);

        /** @psalm-var PDOStatement $this->pdoStatement */
        $this->pdoStatement->nextRowset();
        /** @psalm-var array<string,mixed> $result */
        $result = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
        $this->pdoStatement->closeCursor();

        if (!$this->phpTypecasting) {
            return $result;
        }

        $columns = $this->db->getTableSchema($table)?->getColumns();

        if (empty($columns)) {
            return $result;
        }

        foreach ($result as $name => &$value) {
            $value = $columns[$name]->phpTypecast($value);
        }

        return $result;
    }

    public function showDatabases(): array
    {
        $sql = <<<SQL
        SHOW DATABASES WHERE `Database` NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }

    protected function queryInternal(int $queryMode): mixed
    {
        try {
            return parent::queryInternal($queryMode);
        } catch (IntegrityException $e) {
            if (
                str_starts_with($e->getMessage(), 'SQLSTATE[HY000]: General error: ')
                && in_array(substr($e->getMessage(), 32, 5), ['2006 ', '4031 '], true)
                && $this->db->getTransaction() === null
            ) {
                $this->cancel();
                $this->db->close();

                return parent::queryInternal($queryMode);
            }

            throw $e;
        }
    }

    protected function pdoStatementExecute(): void
    {
        set_error_handler(
            static fn(int $errorNumber, string $errorString): bool
                => str_starts_with($errorString, 'Packets out of order. Expected '),
            E_WARNING,
        );

        try {
            $this->pdoStatement?->execute();
        } finally {
            restore_error_handler();
        }
    }
}
