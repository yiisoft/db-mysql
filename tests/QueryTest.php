<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestSupport\TestQueryTrait;

/**
 * @group mysql
 */
final class QueryTest extends TestCase
{
    use TestQueryTrait;

    public function testQueryIndexHint(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from([new Expression('{{%customer}} USE INDEX (primary)')]);

        $row = $query->one();

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('email', $row);
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');

        /* In MySQL limit and offset arguments must both be non negative integer constant */
        $query->limit(new Expression('2'))->offset(new Expression('1'));

        $result = $query->column();

        /** @todo need fix for this behaviour PHP8.1 + pdo_mysql */
        $this->assertTrue(in_array('2', $result, false));
        $this->assertTrue(in_array('3', $result, false));
        $this->assertFalse(in_array('1', $result, false));
    }
}
