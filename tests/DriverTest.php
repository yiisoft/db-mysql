<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mysql\PDO\PDODriver;
use Yiisoft\Db\TestSupport\TestConnectionTrait;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mysql
 */
final class DriverTest extends TestCase
{
    public function testConnectionCharset(): void
    {
        $pdoDriver = new PDODriver($this->dsn, $this->username, $this->password);

        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($this->charset, array_values($charset)[1]);

        $newCharset = 'latin1';
        $pdoDriver->charset($newCharset);
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($newCharset, array_values($charset)[1]);

    }
}
