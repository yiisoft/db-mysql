<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\DsnSocket;

/**
 * @group mysql
 */
final class DsnSocketTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysql/mysql.sock', 'yiitest', ['charset' => 'utf8']);

        $this->assertSame('mysql', $dsn->driver);
        $this->assertSame('/var/run/mysql/mysql.sock', $dsn->unixSocket);
        $this->assertSame('yiitest', $dsn->databaseName);
        $this->assertSame(['charset' => 'utf8'], $dsn->options);
        $this->assertSame('mysql:unix_socket=/var/run/mysql/mysql.sock;dbname=yiitest;charset=utf8', (string) $dsn);
    }

    public function testConstructDefaults(): void
    {
        $dsn = new DsnSocket();

        $this->assertSame('mysql', $dsn->driver);
        $this->assertSame('/var/run/mysqld/mysqld.sock', $dsn->unixSocket);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame([], $dsn->options);
        $this->assertSame('mysql:unix_socket=/var/run/mysqld/mysqld.sock', (string) $dsn);
    }

    public function testConstructWithEmptyDatabase(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', '', ['charset' => 'utf8']);

        $this->assertSame('mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8', (string) $dsn);
        $this->assertEmpty($dsn->databaseName);
    }
}
