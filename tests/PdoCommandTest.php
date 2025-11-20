<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoCommandTest;

/**
 * @group mysql
 */
final class PdoCommandTest extends CommonPdoCommandTest
{
    use IntegrationTestTrait;
}
