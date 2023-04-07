<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoCommandTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoCommandTest extends CommonPdoCommandTest
{
    use TestTrait;
}
