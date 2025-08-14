<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Column\ColumnFactory;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group mysql
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

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

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()?->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertEquals(true, $db->getActivePDO()?->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
        $db->setEmulatePrepare(false);
        $db->open();

        $this->assertEquals(false, $db->getActivePDO()?->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

    /** @link https://github.com/yiisoft/db-mysql/issues/348 */
    public function testRestartConnectionOnTimeout(): void
    {
        $db = $this->getConnection();

        $db->createCommand('SET SESSION wait_timeout = 1')->execute();

        sleep(2);

        $result = $db->createCommand('SELECT 1')->queryScalar();

        $this->assertSame('1', $result);

        $db->close();
    }

    public function testNotRestartConnectionOnTimeoutInTransaction(): void
    {
        $db = $this->getConnection();
        $db->beginTransaction();

        $db->createCommand('SET SESSION wait_timeout = 1')->execute();

        sleep(2);

        $this->expectException(IntegrityException::class);
        $this->expectExceptionMessageMatches('/SQLSTATE\[HY000\]: General error: (?:2006|4031) /');

        $db->createCommand('SELECT 1')->queryScalar();

        $db->close();
    }

    public function getColumnBuilderClass(): void
    {
        $db = $this->getConnection();

        $this->assertSame(ColumnBuilder::class, $db->getColumnBuilderClass());

        $db->close();
    }

    public function testGetColumnFactory(): void
    {
        $db = $this->getConnection();

        $this->assertInstanceOf(ColumnFactory::class, $db->getColumnFactory());

        $db->close();
    }

    public function testUserDefinedColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache(), $columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());

        $db->close();
    }
}
