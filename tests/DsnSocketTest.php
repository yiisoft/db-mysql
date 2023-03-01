<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\DsnSocket;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnSocketTest extends TestCase
{
    public function testAsString(): void
    {
        $this->assertSame(
            'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest',
            (new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest'))->asString(),
        );
    }

    public function testAsStringWithOptions(): void
    {
        $this->assertSame(
            'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest;charset=utf8',
            (new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8']))->asString(),
        );
    }
}
