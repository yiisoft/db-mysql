<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function strtr;

/**
 * Implements MySQL, MariaDB quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    public function quoteValue(string $value): string
    {
        return "'" . strtr($value, [
            '\\' => '\\\\',
            "\x00" => '\\0',
            "\n" => '\\n',
            "\r" => '\\r',
            "'" => "\'",
            '"' => '\"',
            "\x1a" => '\\Z',
        ]) . "'";
    }
}
