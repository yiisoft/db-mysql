<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;

/**
 * The class ColumnSchemaBuilder for Mysql database.
 */
final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    private QuoterInterface|null $quoter = null;

    /**
     * Builds the unsigned string for column. Defaults to unsupported.
     *
     * @return string a string containing UNSIGNED keyword.
     */
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned() ? ' UNSIGNED' : '';
    }

    /**
     * Builds the comment specification for the column.
     *
     * @throws Exception|InvalidConfigException
     *
     * @return string a string containing the COMMENT keyword and the comment itself.
     */
    protected function buildCommentString(): string
    {
        if ($this->quoter === null) {
            throw new InvalidConfigException('Quoter not setted.');
        }

        return $this->getComment() !== null ? ' COMMENT '
            . (string) $this->quoter->quoteValue($this->getComment()) : '';
    }

    public function asString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{length}{comment}{check}{append}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{check}',
            default => '{type}{length}{notnull}{default}{unique}{comment}{append}{check}',
        };

        return $this->buildCompleteString($format);
    }

    public function setQuoter(QuoterInterface $quoter): self
    {
        $this->quoter = $quoter;
        return $this;
    }
}
