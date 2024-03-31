<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Schema\SchemaInterface;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'mysql';

    /**
     * @return array
     */
    public static function types(): array
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

    /**
     * @return array
     */
    public static function createColumnTypes(): array
    {
        $types = parent::createColumnTypes();
        $types['integer'][0] = '`column` int(11)';

        $types['uuid'][0] = '`column` binary(16)';
        $types['uuid not null'][0] = '`column` binary(16) NOT NULL';

        $types['uuid with default'][0] = '`column` binary(16) DEFAULT (UUID_TO_BIN(\'875343b3-6bd0-4bec-81bb-aa68bb52d945\'))';
        $types['uuid with default'][3] = [['defaultExpression', '(UUID_TO_BIN(\'875343b3-6bd0-4bec-81bb-aa68bb52d945\'))']];

        $types['uuid pk'][0] = '`column` binary(16) PRIMARY KEY';
        $types['uuid pk not null'][0] = '`column` binary(16) PRIMARY KEY NOT NULL';

        $types['uuid pk not null with default'][0] = '`column` binary(16) PRIMARY KEY NOT NULL DEFAULT (UUID_TO_BIN(UUID()))';
        $types['uuid pk not null with default'][3] = [['notNull'], ['defaultExpression', '(UUID_TO_BIN(UUID()))']];

        return $types;
    }
}
