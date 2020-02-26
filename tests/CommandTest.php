<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Tests\CommandTest as AbstractCommandTest;

class CommandTest extends AbstractCommandTest
{
    public ?string $driverName = 'mysql';

    protected string $upsertTestCharCast = 'CONVERT([[address]], CHAR)';

    public function testAddDropCheck(): void
    {
        $this->markTestSkipped('MySQL does not support adding/dropping check constraints.');
    }
}
