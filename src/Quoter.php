<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use PDO;
use Yiisoft\Db\Schema\Quoter as BaseQuoter;

/**
 * @todo Remove or use? Where is question
 * Temporary not used. Need add more tests for many charset
 */
final class Quoter extends BaseQuoter
{
    /**
     * @psalm-param string[]|string $columnQuoteCharacter
     * @psalm-param string[]|string $tableQuoteCharacter
     */
    public function __construct(
        array|string $columnQuoteCharacter,
        array|string $tableQuoteCharacter,
        string $tablePrefix = '',
        protected PDO|null $pdo = null
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix, $pdo);
    }

    public function quoteValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return "'" . str_replace(
            ["\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a"],
            ["\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z"],
            $value) . "'";
    }
}
