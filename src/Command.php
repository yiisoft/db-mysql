<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;

/**
 * Implements a database command that can be executed with a PDO (PHP Data Object) database connection for MySQL,
 * MariaDB.
 */
final class Command extends AbstractPdoCommand
{
    /**
     * @param string $table
     * @param array $columns
     * @return bool|array
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     * @throws \Yiisoft\Db\Exception\InvalidCallException
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @throws \Yiisoft\Db\Exception\NotSupportedException
     */
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
                $result[$name] = $this->db->getLastInsertID((string)$tableSchema?->getSequenceName());
                continue;
            }

            /** @psalm-var mixed */
            $result[$name] = $columns[$name] ?? $tableSchema?->getColumn($name)?->getDefaultValue();
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     */
    public function showDatabases(): array
    {
        $sql = <<<SQL
        SHOW DATABASES WHERE `Database` NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }
}
