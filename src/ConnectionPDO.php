<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use Psr\Log\LogLevel;
use Yiisoft\Db\Driver\PDO\AbstractConnectionPDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Database connection class prefilled for MySQL Server.
 * The class Connection represents a connection to a database via [PDO](https://secure.php.net/manual/en/book.pdo.php).
 */
final class ConnectionPDO extends AbstractConnectionPDO
{
    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Closing DB connection: ' . $this->driver->getDsn() . ' ' . __METHOD__,
            );

            // Solution for close connections from https://stackoverflow.com/questions/18277233/pdo-closing-connection
            try {
                $this->pdo->query('KILL CONNECTION_ID()');
            } catch (\Exception) {
            }

            $this->pdo = null;
            $this->transaction = null;
        }
    }

    public function createCommand(string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new CommandPDO($this, $this->queryCache);

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
        return new TransactionPDO($this);
    }

    /**
     * @throws Exception|InvalidConfigException
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder(
                $this->getQuoter(),
                $this->getSchema(),
            );
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter('`', '`', $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->schemaCache);
        }

        return $this->schema;
    }
}
