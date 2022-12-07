<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Provider\AbstractColumnSchemaBuilderProvider;

final class ColumnSchemaBuilderProvider extends AbstractColumnSchemaBuilderProvider
{
    public function types(): array
    {
        $types = parent::types();

        $types[0][0] = 'integer UNSIGNED NULL DEFAULT NULL';
        $types[1][0] = 'integer(10) UNSIGNED';
        $types[2][0] = 'integer(10) COMMENT \'test\'';

        return array_merge(
            $types,
            [
                ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [['unsigned']]],

                /**
                 * {@link https://github.com/yiisoft/yii2/issues/11945}, real test against database.
                 */
                [
                    'string(50) NOT NULL COMMENT \'Property name\' COLLATE ascii_general_ci',
                    Schema::TYPE_STRING, 50,
                    [
                        ['comment', 'Property name'],
                        ['append', 'COLLATE ascii_general_ci'],
                        ['notNull'],
                    ]
                ],
                ['integer(10) AFTER `test`', Schema::TYPE_INTEGER, 10, [['after', 'test']]],
                ['integer(10) FIRST', Schema::TYPE_INTEGER, 10, [['first']]],
            ],
        );
    }
}
