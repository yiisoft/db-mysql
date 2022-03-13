<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\PDO;

use PDOException;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class CommandPDOMysql extends Command
{
    public function __construct(private ConnectionPDOInterface $db, QueryCache $queryCache)
    {
        parent::__construct($queryCache);
    }

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

        $tableSchema = $this->queryBuilder()->schema()->getTableSchema($table);
        $tablePrimaryKeys = $tableSchema?->getPrimaryKey() ?? [];

        $result = [];
        foreach ($tablePrimaryKeys as $name) {
            if ($tableSchema?->getColumn($name)?->isAutoIncrement()) {
                $result[$name] = $this->queryBuilder()->schema()->getLastInsertID((string) $tableSchema?->getSequenceName());
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

    /**
     * @throws Exception|InvalidConfigException|PDOException
     */
    public function prepare(?bool $forRead = null): void
    {
        if (isset($this->pdoStatement)) {
            $this->bindPendingParams();

            return;
        }

        $sql = $this->getSql();

        if ($this->db->getTransaction()) {
            /** master is in a transaction. use the same connection. */
            $forRead = false;
        }

        if ($forRead || ($forRead === null && $this->db->getSchema()->isReadQuery($sql))) {
            $pdo = $this->db->getSlavePdo();
        } else {
            $pdo = $this->db->getMasterPdo();
        }

        try {
            $this->pdoStatement = $pdo?->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            /** @var array|null */
            $errorInfo = $e->errorInfo ?? null;

            throw new Exception($message, $errorInfo, $e);
        }
    }

    protected function getCacheKey(string $rawSql): array
    {
        return [
            __CLASS__,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
            $rawSql,
        ];
    }

    protected function internalExecute(?string $rawSql): void
    {
        $attempt = 0;

        while (true) {
            try {
                if (
                    ++$attempt === 1
                    && $this->isolationLevel !== null
                    && $this->db->getTransaction() === null
                ) {
                    $this->db->transaction(fn (?string $rawSql) => $this->internalExecute($rawSql), $this->isolationLevel);
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
