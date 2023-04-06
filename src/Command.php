<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDOException;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for MySQL,
 * MariaDB.
 */
final class Command extends AbstractPdoCommand
{
    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $this->setSql($sql)->bindValues($params);

        if (!$this->execute()) {
            return false;
        }

        $tableSchema = $this->db->getSchema()->getTableSchema($table);
        $tablePrimaryKeys = $tableSchema?->getPrimaryKey() ?? [];
        $result = [];

        foreach ($tablePrimaryKeys as $name) {
            if ($tableSchema?->getColumn($name)?->isAutoIncrement()) {
                $result[$name] = $this->db->getLastInsertID((string) $tableSchema?->getSequenceName());
                continue;
            }

            /** @psalm-var mixed */
            $result[$name] = $columns[$name] ?? $tableSchema?->getColumn($name)?->getDefaultValue();
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

    protected function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    /**
     * @psalm-suppress UnusedClosureParam
     *
     * @throws Throwable
     */
    protected function internalExecute(string|null $rawSql): void
    {
        $attempt = 0;

        while (true) {
            try {
                if (
                    ++$attempt === 1
                    && $this->isolationLevel !== null
                    && $this->db->getTransaction() === null
                ) {
                    $this->db->transaction(
                        fn (PdoConnectionInterface $db) => $this->internalExecute($rawSql),
                        $this->isolationLevel,
                    );
                } else {
                    $this->pdoStatement?->execute();
                }
                break;
            } catch (PDOException $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
