<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionPDOTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionTest extends CommonConnectionPDOTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testInitConnection(): void
    {
        $db = $this->getConnection();

        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertTrue($db->getEmulatePrepare());

        $db->setEmulatePrepare(false);

        $this->assertFalse($db->getEmulatePrepare());

        $db->close();
    }

    public function testGetName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('mysql', $db->getName());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()?->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertsAME(1, $db->getActivePDO()?->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
        $db->setEmulatePrepare(false);
        $db->open();

        $this->assertSame(0, $db->getActivePDO()?->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection(true);

        $transaction = $db->beginTransaction(TransactionInterface::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::READ_COMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::SERIALIZABLE);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnection(true);

        $result = $db->transaction(
            static function (ConnectionInterface $db) {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                return true;
            },
            TransactionInterface::READ_UNCOMMITTED,
        );

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertSame('1', $profilesCount, 'profile should be inserted in transaction shortcut');
    }
}
