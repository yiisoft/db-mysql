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
            'mysql:host=localhost;dbname=yiitest',
            (new Dsn('mysql', 'localhost', 'yiitest'))->asString(),
        );
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
