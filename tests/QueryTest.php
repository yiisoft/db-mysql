<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Mysql\Tests\Support\TestConnection;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonQueryTest;

/**
 * @group mysql
 */
final class QueryTest extends CommonQueryTest
{
    use IntegrationTestTrait;

    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @link https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $selectExpression = "concat(customer.name,' in ', p.description) name";

        $result = (new Query($db))
            ->select([$selectExpression])
            ->from('customer')
            ->innerJoin('profile p', '[[customer]].[[profile_id]] = [[p]].[[id]]')
            ->indexBy('id')
            ->column();

        $this->assertSame([1 => 'user1 in profile customer 1', 3 => 'user3 in profile customer 3'], $result);
    }

    public function testQueryIndexHint(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $query = (new Query($db))->from([new Expression('{{%customer}} USE INDEX (primary)')]);

        $row = $query->one();

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('email', $row);

        $db->close();
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');

        /* In MySQL limit and offset arguments must both be non negative integer constant */
        $query->limit(new Expression('2'))->offset(new Expression('1'));

        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains('2', $result);
        $this->assertContains('3', $result);
        $this->assertNotContains('1', $result);

        $db->close();
    }

    public function testWithQuery(): void
    {
        $serverVersion = TestConnection::getServerVersion();

        if (
            !str_contains($serverVersion, 'MariaDB')
            && version_compare($serverVersion, '8.0.0', '<')
        ) {
            self::markTestSkipped('CTE not supported in MySQL versions below 8.0.0');
        }

        parent::testWithQuery();
    }

    public function testWithQueryRecursive(): void
    {
        $serverVersion = TestConnection::getServerVersion();

        if (
            !str_contains($serverVersion, 'MariaDB')
            && version_compare($serverVersion, '8.0.0', '<')
        ) {
            self::markTestSkipped('CTE not supported in MySQL versions below 8.0.0');
        }

        parent::testWithQueryRecursive();
    }
}
