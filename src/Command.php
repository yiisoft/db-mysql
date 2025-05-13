<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function in_array;
use function str_starts_with;
use function substr;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for MySQL,
 * MariaDB.
 */
final class Command extends AbstractPdoCommand
{
    public function insertWithReturningPks(string $table, array|QueryInterface $columns): array|false
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);

        return $this->executeWithReturningPks($sql, $params, $table, $columns, __METHOD__);
    }

    public function upsertWithReturningPks(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true
    ): array|false {
        $params = [];
        $sql = $this->db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $params);

        return $this->executeWithReturningPks($sql, $params, $table, $insertColumns, __METHOD__);
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

    public function showDatabases(): array
    {
        $sql = <<<SQL
        SHOW DATABASES WHERE `Database` NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }

    private function executeWithReturningPks(
        string $sql,
        array $params,
        string $table,
        array|QueryInterface $columns,
        string $method,
    ): array|false {
        $this->setSql($sql)->bindValues($params);

        $tableSchema = $this->db->getSchema()->getTableSchema($table);

        if ($this->execute() === 0) {
            return false;
        }

        $primaryKeys = $tableSchema?->getPrimaryKey() ?? [];

        if (empty($primaryKeys)) {
            return [];
        }

        if ($columns instanceof QueryInterface) {
            throw new NotSupportedException($method . '() not supported for QueryInterface by MySQL.');
        }

        $result = [];

        /** @var TableSchema $tableSchema */
        foreach ($primaryKeys as $name) {
            /** @var ColumnInterface $column */
            $column = $tableSchema->getColumn($name);

            if ($column->isAutoIncrement()) {
                $value = $this->db->getLastInsertId();
            } else {
                $value = $columns[$name] ?? $column->getDefaultValue();
            }

            if ($this->phpTypecasting) {
                $value = $column->phpTypecast($value);
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
