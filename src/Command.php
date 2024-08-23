<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\IntegrityException;

use function str_starts_with;

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

        $tableSchema = $this->db->getSchema()->getTableSchema($table);

        if (!$this->execute()) {
            return false;
        }

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

    protected function queryInternal(int $queryMode): mixed
    {
        try {
            return parent::queryInternal($queryMode);
        } catch (IntegrityException $e) {
            if (str_starts_with($e->getMessage(), 'SQLSTATE[HY000]: General error: 2006 ')) {
                $this->db->close();
                $this->cancel();

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
}
