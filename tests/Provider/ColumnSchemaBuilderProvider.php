<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Schema\SchemaInterface;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
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
                ['integer UNSIGNED', SchemaInterface::TYPE_INTEGER, null, [['unsigned']]],

                /**
                 * {@link https://github.com/yiisoft/yii2/issues/11945}, real test against database.
                 */
                [
                    'string(50) NOT NULL COMMENT \'Property name\' COLLATE ascii_general_ci',
                    SchemaInterface::TYPE_STRING, 50,
                    [
                        ['comment', 'Property name'],
                        ['append', 'COLLATE ascii_general_ci'],
                        ['notNull'],
                    ],
                ],
            ],
        );
    }
}
