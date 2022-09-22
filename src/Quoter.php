<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use Yiisoft\Db\Schema\Quoter as BaseQuoter;

final class Quoter extends BaseQuoter
{
    /**
     * @psalm-param string[]|string $columnQuoteCharacter
     * @psalm-param string[]|string $tableQuoteCharacter
     */
    public function __construct(
        array|string $columnQuoteCharacter,
        array|string $tableQuoteCharacter,
        string $tablePrefix = ''
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix);
    }

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
