<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\PDO;

use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\AwareTrait\LoggerAwareTrait;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionInterface;

final class TransactionPDOMysql implements TransactionInterface
{
    use LoggerAwareTrait;

    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_COMMITTED = 'READ COMMITTED';

    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const SERIALIZABLE = 'SERIALIZABLE';

    private int $level = 0;

    public function __construct(private ConnectionPDOInterface $db)
    {
    }

    public function begin(?string $isolationLevel = null): void
    {
        $this->db->open();

        if ($this->level === 0) {
            if ($isolationLevel !== null) {
                $this->db->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }

            $this->logger?->log(
                LogLevel::DEBUG,
                'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : '')
                . ' ' . __METHOD__
            );
            $this->db->getPDO()?->beginTransaction();
            $this->level = 1;
            return;
        }

        $schema = $this->db->getSchema();

        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Set savepoint ' . $this->level . ' ' . __METHOD__);
            $schema->createSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Transaction not started: nested transaction not supported ' . __METHOD__
            );
            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->level++;
    }

    /**
     * Commits a transaction.
     *
     * @throws Exception|Throwable if the transaction is not active
     */
    public function commit(): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->level--;

        if ($this->level === 0) {
            $this->logger?->log(LogLevel::DEBUG, 'Commit transaction ' . __METHOD__);
            $this->db->getPDO()?->commit();
            return;
        }

        $schema = $this->db->getSchema();

        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Release savepoint ' . $this->level . ' ' . __METHOD__);
            $schema->releaseSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not committed: nested transaction not supported ' . __METHOD__
            );
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isActive(): bool
    {
        return $this->level > 0 && $this->db->isActive();
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBack(): void
    {
        if (!$this->isActive()) {
            /**
             * do nothing if transaction is not active: this could be the transaction is committed but the event handler
             * to "commitTransaction" throw an exception
             */
            return;
        }

        $this->level--;
        if ($this->level === 0) {
            $this->logger?->log(LogLevel::INFO, 'Roll back transaction ' . __METHOD__);
            $this->db->getPDO()?->rollBack();
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Roll back to savepoint ' . $this->level . ' ' . __METHOD__);
            $schema->rollBackSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not rolled back: nested transaction not supported ' . __METHOD__
            );
        }
    }

    public function setIsolationLevel(string $level): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }

        $this->logger?->log(
            LogLevel::DEBUG,
            'Setting transaction isolation level to ' . $this->level . ' ' . __METHOD__
        );
        $this->db->getSchema()->setTransactionIsolationLevel($level);
    }
}
