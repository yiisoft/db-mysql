<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

final class ColumnDefinitionParserProvider extends \Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider
{
    public static function parse(): array
    {
        return [
            ...parent::parse(),
            ["enum('a','b','c')", ['type' => 'enum', 'enumValues' => ['a', 'b', 'c']]],
            ["enum('a','b','c') NOT NULL", ['type' => 'enum', 'enumValues' => ['a', 'b', 'c'], 'notNull' => true]],
        ];
    }
}

