<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function columnFactory(): ColumnFactoryInterface
    {
        return new ColumnFactory();
    }
}
