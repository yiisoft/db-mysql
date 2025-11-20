<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Mysql\Tests\Support\TestConnection;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mysql
 */
final class PdoDriverTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testConnectionCharset(): void
    {
        $db = $this->getSharedConnection();

        $pdo = $db->getActivePdo();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('utf8mb4', array_values($charset)[1]);

        $pdoDriver = TestConnection::createDriver();
        $newCharset = 'latin1';
        $pdoDriver->charset($newCharset);
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW VARIABLES LIKE \'character_set_client\'', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase($newCharset, array_values($charset)[1]);

        unset($pdo);
    }

    public function testCharsetDefault(): void
    {
        $db = $this->getSharedConnection();
        $db->open();
        $command = $db->createCommand();

        $this->assertSame('utf8mb4', $command->setSql('SELECT @@character_set_client')->queryScalar());
    }
}
