<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

class SqlParserProvider extends \Yiisoft\Db\Tests\Provider\SqlParserProvider
{
    public static function getNextPlaceholder(): array
    {
        return [
            ...parent::getNextPlaceholder(),
            [
                '`:field` = :name AND age = :age',
                ':name',
                11,
            ],
            [
                '`:fie\\`ld` = :name AND age = :age',
                ':name',
                13,
            ],
            [
                '":fie\\"ld" = :name AND age = :age',
                ':name',
                13,
            ],
            [
                "name = ':na\\'me' AND age = :age",
                ':age',
                27,
            ],
        ];
    }
}
