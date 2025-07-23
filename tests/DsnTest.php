<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\Dsn;

/**
 * @group mysql
 */
final class DsnTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3307', ['charset' => 'utf8']);

        $this->assertSame('mysql', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('yiitest', $dsn->databaseName);
        $this->assertSame('3307', $dsn->port);
        $this->assertSame(['charset' => 'utf8'], $dsn->options);
        $this->assertSame('mysql:host=localhost;dbname=yiitest;port=3307;charset=utf8', (string) $dsn);
    }

    public function testConstructDefaults(): void
    {
        $dsn = new Dsn();

        $this->assertSame('mysql', $dsn->driver);
        $this->assertSame('127.0.0.1', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('3306', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('mysql:host=127.0.0.1;port=3306', (string) $dsn);
    }

    public function testConstructWithEmptyPort(): void
    {
        $dsn = new Dsn(port: '');

        $this->assertSame('mysql', $dsn->driver);
        $this->assertSame('127.0.0.1', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('mysql:host=127.0.0.1', (string) $dsn);
    }
}
