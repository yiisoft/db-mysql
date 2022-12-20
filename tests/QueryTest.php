<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQueryTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryTest extends AbstractQueryTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryIndexHint(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from([new Expression('{{%customer}} USE INDEX (primary)')]);

        $row = $query->one();

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('email', $row);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');

        /* In MySQL limit and offset arguments must both be non negative integer constant */
        $query->limit(new Expression('2'))->offset(new Expression('1'));

        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains('2', $result);
        $this->assertContains('3', $result);
        $this->assertNotContains('1', $result);
    }
}
