<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Pdo;

use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends \Yiisoft\Db\Tests\Common\Pdo\CommonCommandTest
{
    use TestTrait;
}
