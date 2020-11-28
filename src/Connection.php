<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use function constant;
use PDO;
use Yiisoft\Db\Command\Command;

use Yiisoft\Db\Connection\Connection as AbstractConnection;

/**
 * Database connection class prefilled for MYSQL Server.
 */
final class Connection extends AbstractConnection
{
    private ?Schema $schema = null;

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

        $command = new Command($this->getProfiler(), $this->getLogger(), $this, $this->getQueryCache(), $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        return $this->schema ?? ($this->schema = new Schema($this, $this->getSchemaCache()));
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
        if ($this->getPDO() !== null) {
            $this->getPDO()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
                $this->getPDO()->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
            }

            if ($this->getCharset() !== null) {
                $this->getPDO()->exec('SET NAMES ' . $this->getPDO()->quote($this->getCharset()));
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
