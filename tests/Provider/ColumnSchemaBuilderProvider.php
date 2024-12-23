<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'mysql';

    public static function types(): array
    {
        $types = parent::types();

        $types[0][0] = 'integer UNSIGNED NULL DEFAULT NULL';
        $types[1][0] = 'integer(10) UNSIGNED';
        $types[2][0] = 'integer(10) COMMENT \'test\'';

        return [
            ...$types,
            ['integer UNSIGNED', ColumnType::INTEGER, null, [['unsigned']]],

            /**
             * {@link https://github.com/yiisoft/yii2/issues/11945}, real test against database.
             */
            [
                'string(50) NOT NULL COMMENT \'Property name\' COLLATE ascii_general_ci',
                ColumnType::STRING, 50,
                [
                    ['comment', 'Property name'],
                    ['append', 'COLLATE ascii_general_ci'],
                    ['notNull'],
                ],
            ],
        ];
    }
}
