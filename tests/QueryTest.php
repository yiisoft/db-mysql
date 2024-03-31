<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonQueryTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryTest extends CommonQueryTest
{
    use TestTrait;

    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @link https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        $db = $this->getConnection(true);

        $selectExpression = "concat(customer.name,' in ', p.description) name";

        $result = (new Query($db))
            ->select([$selectExpression])
            ->from('customer')
            ->innerJoin('profile p', '[[customer]].[[profile_id]] = [[p]].[[id]]')
            ->indexBy('id')
            ->column();

        $this->assertSame([1 => 'user1 in profile customer 1', 3 => 'user3 in profile customer 3'], $result);
    }

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

        /* In MySQL limit and offset arguments must both be non-negative integer constant */
        $query->limit(new Expression('2'))->offset(new Expression('1'));

        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains('2', $result);
        $this->assertContains('3', $result);
        $this->assertNotContains('1', $result);
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testWithQuery(): void
    {
        $db = $this->getConnection();

        if (
            !str_contains($db->getServerVersion(), 'MariaDB')
            && version_compare($db->getServerVersion(), '8.0.0', '<')
        ) {
            self::markTestSkipped('CTE not supported in MySQL versions below 8.0.0');
        }

        parent::testWithQuery();
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testWithQueryRecursive():  void
    {
        $db = $this->getConnection();

        if (
            !str_contains($db->getServerVersion(), 'MariaDB')
            && version_compare($db->getServerVersion(), '8.0.0', '<')
        ) {
            self::markTestSkipped('CTE not supported in MySQL versions below 8.0.0');
        }

        parent::testWithQueryRecursive();
    }
}
