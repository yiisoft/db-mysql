<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Builder;

use Yiisoft\Db\Expression\ExpressionBuilder as BaseExpressionBuilder;
use Yiisoft\Db\Mysql\SqlParser;

final class ExpressionBuilder extends BaseExpressionBuilder
{
    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
