<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Driver\PDODriver;
use Yiisoft\Db\Mysql\PDO\ConnectionPDOMysql;
use Yiisoft\Db\TestSupport\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected string $drivername = 'mysql';
    protected string $dsn = 'mysql:host=127.0.0.1;dbname=yiitest;port=3306';
    protected string $username = 'root';
    protected string $password = '';
    protected string $charset = 'UTF8MB4';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected ?ConnectionPDOMysql $db = null;

    /**
     * @param bool $reset whether to clean up the test database.
     *
     * @return ConnectionPDOMysql
     */
    protected function getConnection(
        $reset = false,
        ?string $dsn = null,
        string $fixture = __DIR__ . '/Fixture/mysql.sql'
    ): ConnectionPDOMysql {
        $pdoDriver = new PDODriver($dsn ?? $this->dsn, $this->username, $this->password);
        $this->db = new ConnectionPDOMysql($pdoDriver, $this->createQueryCache(), $this->createSchemaCache());
        $this->db->setLogger($this->createLogger());
        $this->db->setProfiler($this->createProfiler());

        if ($reset === false) {
            return $this->db;
        }

        try {
            $this->prepareDatabase($this->db, $fixture);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db?->close();
        unset(
            $this->cache,
            $this->connection,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
