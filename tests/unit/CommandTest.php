<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\mysql\tests;

/**
 * @group db
 * @group mysql
 */
class CommandTest extends \yii\db\tests\unit\CommandTest
{
    public $driverName = 'mysql';

    protected $upsertTestCharCast = 'CONVERT([[address]], CHAR)';

    public function testAddDropCheck()
    {
        $this->markTestSkipped('MySQL does not support adding/dropping check constraints.');
    }
}
