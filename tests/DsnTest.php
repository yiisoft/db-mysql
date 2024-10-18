<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\Dsn;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnTest extends TestCase
{
    public function testAsString(): void
    {
        $this->assertSame(
            'mysql:host=localhost;dbname=yiitest;port=3306',
            (new Dsn(databaseName: 'yiitest'))->asString(),
        );
    }

    public function testAsStringWithDatabaseName(): void
    {
        $this->assertSame('mysql:host=localhost;port=3306', (new Dsn('mysql', 'localhost'))->asString());
    }

    public function testAsStringWithDatabaseNameWithEmptyString(): void
    {
        $this->assertSame('mysql:host=localhost;port=3306', (new Dsn('mysql', 'localhost', ''))->asString());
    }

    public function testAsStringWithDatabaseNameWithNull(): void
    {
        $this->assertSame('mysql:host=localhost;port=3306', (new Dsn('mysql', 'localhost', null))->asString());
    }

    public function testAsStringWithOptions(): void
    {
        $this->assertSame(
            'mysql:host=localhost;dbname=yiitest;port=3307;charset=utf8',
            (new Dsn('mysql', 'localhost', 'yiitest', '3307', ['charset' => 'utf8']))->asString(),
        );
    }

    public function testAsStringWithPort(): void
    {
        $this->assertSame(
            'mysql:host=localhost;dbname=yiitest;port=3307',
            (new Dsn('mysql', 'localhost', 'yiitest', '3307'))->asString(),
        );
    }
}
