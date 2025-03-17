<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\Driver\Pdo\PdoCommandInterface;
use Yiisoft\Db\Mysql\Column\ColumnFactory;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for MySQL, MariaDB.
 *
 * @link https://www.php.net/manual/en/ref.pdo-mysql.php
 */
final class Connection extends AbstractPdoConnection
{
    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Closing DB connection: ' . $this->driver->getDsn() . ' ' . __METHOD__,
            );

            // Solution for close connections {@link https://stackoverflow.com/questions/18277233/pdo-closing-connection}
            try {
                $this->pdo->exec('KILL CONNECTION_ID()');
            } catch (Throwable) {
            }

            $this->pdo = null;
            $this->transaction = null;
        }
    }

    public function createCommand(?string $sql = null, array $params = []): PdoCommandInterface
    {
        $command = new Command($this);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new Transaction($this);
    }

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return new ColumnFactory();
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder ??= new QueryBuilder($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter ??= new Quoter('`', '`', $this->getTablePrefix());
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema ??= new Schema($this, $this->schemaCache);
    }
}
