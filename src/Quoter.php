<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function is_string;
use function str_replace;

/**
 * Implements MySQL, MariaDB quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function quoteValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return "'" . str_replace(
            ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'],
            $value
        ) . "'";
    }
}
