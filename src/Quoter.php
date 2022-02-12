<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;
use Yiisoft\Db\Schema\QuoterInterface;

final class Quoter extends BaseQuoter implements QuoterInterface
{
    public function __construct(
        private array|string $columnQuoteCharacter,
        private array|string $tableQuoteCharacter,
        private string $tablePrefix = ''
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix);
    }

    public function quoteValue(int|string $value): int|string
    {
        if (!is_string($value)) {
            return $value;
        }

        return "'" . preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]~u', '\\\$0', $value) . "'";
    }
}
