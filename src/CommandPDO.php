<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDOException;
use Yiisoft\Db\Driver\PDO\CommandPDO as AbstractCommandPDO;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class CommandPDO extends AbstractCommandPDO
{
    /**
     * @inheritDoc
     */
    public function insertEx(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insertEx($table, $columns, $params);
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

            /** @var mixed */
            $result[$name] = $columns[$name] ?? $tableSchema?->getColumn($name)?->getDefaultValue();
        }

        return $result;
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    public function schema(): SchemaInterface
    {
        return $this->db->getSchema();
    }

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
                        fn (string|null $rawSql) => $this->internalExecute($rawSql),
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
