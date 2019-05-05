<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\ActiveRecord\Tests\Data\Storage;

/**
 * @group db
 * @group mysql
 */
class ActiveRecordTest extends \Yiisoft\ActiveRecord\Tests\Unit\ActiveRecordTest
{
    public $driverName = 'mysql';

    public function testJsonColumn()
    {
        if (version_compare($this->getConnection()->getSchema()->getServerVersion(), '5.7', '<')) {
            $this->markTestSkipped('JSON columns are not supported in MySQL < 5.7');
        }

        $data = [
            'obj'              => ['a' => ['b' => ['c' => 2.7418]]],
            'array'            => [1, 2, null, 3],
            'null_field'       => null,
            'boolean_field'    => true,
            'last_update_time' => '2018-02-21',
        ];

        $storage = new Storage();
        $storage->data = $data;
        $this->assertTrue($storage->save(), 'Storage can be saved');
        $this->assertNotNull($storage->id);

        $retrievedStorage = Storage::findOne($storage->id);
        $this->assertSame($data, $retrievedStorage->data, 'Properties are restored from JSON to array without changes');

        $retrievedStorage->data = ['updatedData' => $data];
        $this->assertSame(1, $retrievedStorage->update(), 'Storage can be updated');

        $retrievedStorage->refresh();
        $this->assertSame(['updatedData' => $data], $retrievedStorage->data, 'Properties have been changed during update');
    }
}
