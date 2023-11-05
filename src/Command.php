<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDOException;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
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
}
