<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Db\Mysql\PDODriver;

/**
 * @group mysql
 */
final class DriverTest extends TestCase
{
    public function testConnectionCharset(): void
    {
        $db = $this->getConnection();
        $pdo = $db->getActivePDO();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($this->charset, array_values($charset)[1]);

        $pdoDriver = new PDODriver($this->dsn, $this->username, $this->password);
        $newCharset = 'latin1';
        $pdoDriver->charset($newCharset);
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($newCharset, array_values($charset)[1]);
    }
}
