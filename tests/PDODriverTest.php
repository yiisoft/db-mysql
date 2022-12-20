<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\PDODriver;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PDODriverTest extends TestCase
{
    use TestTrait;

    public function testConnectionCharset(): void
    {
        $db = $this->getConnection();

        $pdo = $db->getActivePDO();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('utf8mb4', array_values($charset)[1]);

        $pdoDriver = new PDODriver('mysql:host=127.0.0.1;dbname=yiitest;port=3306', 'root', '');
        $newCharset = 'latin1';
        $pdoDriver->setCharset($newCharset);
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase($newCharset, array_values($charset)[1]);
    }
}
