<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Throwable;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoConnectionTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoConnectionTest extends CommonPdoConnectionTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertID(): void
    {
        $db = $this->getConnection();

        $tableName = 'test';

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $this->assertSame(
            1,
            $command->setSql(
                <<<SQL
                SELECT @@innodb_autoinc_lock_mode
                SQL,
            )->execute(),
        );

        $command->setSql(
            <<<SQL
            CREATE TABLE `$tableName` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `val` INT(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `i1` (`val`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            SQL,
        )->execute();
        $command->setSQL(
            <<<SQL
            INSERT INTO $tableName (val) VALUES (1), (2);
            SQL
        )->execute();

        $this->assertSame('1', $db->getLastInsertID());

        $command->setSQL(
            <<<SQL
            INSERT INTO $tableName (val) VALUES (3), (4), (5);
            SQL
        )->execute();

        /**
         * Although the second INSERT statement inserted three new rows into test, the ID generated for the first of
         * these rows was 3, and it is this value that is returned by LAST_INSERT_ID() for the following SELECT
         * statement.
         */
        $this->assertSame('3', $db->getLastInsertID());

        $command->setSQL(
            <<<SQL
            INSERT IGNORE INTO $tableName (val) VALUES (3), (4), (5);
            SQL
        )->execute();

        /**
         * If you use INSERT IGNORE and the row is ignored, the LAST_INSERT_ID() remains unchanged from the current
         * value (or 0 is returned if the connection has not yet performed a successful INSERT) and, for
         * non-transactional tables, the AUTO_INCREMENT counter is not incremented.
         */
        $this->assertSame('0', $db->getLastInsertID());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testTransactionAutocommit(): void
    {
        $db = $this->getConnection(true);
        $db->transaction(
            function (PdoConnectionInterface $db) {
                $this->assertTrue($db->getTransaction()->isActive());

                // create table will cause the transaction to be implicitly committed
                // (see https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html)
                $name = 'test_implicit_transaction_table';
                $db->createCommand()->createTable($name, ['id' => 'pk'])->execute();
                $db->createCommand()->dropTable($name)->execute();
            });
        // If we made it this far without an error, then everything's working
    }
}
