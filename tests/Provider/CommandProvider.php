<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\IndexMethod;
use Yiisoft\Db\Mysql\IndexType;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    public static function createIndex(): array
    {
        return [
            ...parent::createIndex(),
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE, null],
            [['col1' => ColumnBuilder::text()], ['col1'], IndexType::FULLTEXT, null],
            [['col1' => 'point NOT NULL'], ['col1'], IndexType::SPATIAL, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], null, IndexMethod::BTREE],
            [['col1' => ColumnBuilder::integer()], ['col1'], null, IndexMethod::HASH],
        ];
    }
}
