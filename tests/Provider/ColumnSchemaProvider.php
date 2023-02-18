<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

final class ColumnSchemaProvider
{
    public static function bigIntValue(): array
    {
        return [
            ['8817806877'],
            ['3797444208'],
            ['3199585540'],
            ['1389831585'],
            ['922337203685477580'],
            ['9223372036854775807'],
            ['-9223372036854775808'],
        ];
    }
}
