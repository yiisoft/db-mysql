<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;
use Yiisoft\Db\Schema\QuoterInterface;

final class Quoter extends BaseQuoter implements QuoterInterface
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

        return "'" . preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C]~u', '\\\$0', $value) . "'";
    }
}
