<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\Connection as AbstractConnection;

use function constant;

/**
 * Database connection class prefilled for MYSQL Server.
 */
final class Connection extends AbstractConnection
{
    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     *
     * @return Command the DB command
     */
    public function createCommand(?string $sql = null, array $params = []): Command
    {
        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        $command = new Command($this, $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        return new Schema($this);
    }

    /**
     * Creates the PDO instance.
     *
     * This method is called by {@see open} to establish a DB connection. The default implementation will create a PHP
     * PDO instance. You may override this method if the default PDO needs to be adapted for certain DBMS.
     *
     * @return PDO the pdo instance
     */
    protected function createPdoInstance(): PDO
    {
        return new PDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        $pdo = $this->getPDO();

        if ($pdo !== null) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
            }

            if ($this->getCharset() !== null) {
                $pdo->exec('SET NAMES ' . $pdo->quote($this->getCharset()));
            }
        }
    }

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }
}
