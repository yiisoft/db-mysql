<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonBatchQueryResultTest;

/**
 * @group mysql
 */
final class BatchQueryResultTest extends CommonBatchQueryResultTest
{
    use IntegrationTestTrait;
}
