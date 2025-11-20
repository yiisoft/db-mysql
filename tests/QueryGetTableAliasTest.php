<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonQueryGetTableAliasTest;

/**
 * @group mysql
 */
final class QueryGetTableAliasTest extends CommonQueryGetTableAliasTest
{
    use IntegrationTestTrait;
}
