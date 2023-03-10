<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;

/**
 * It's a utility that provides a convenient way to create column schema for use with {@see `\Yiisoft\Db\Mysql\Schema`}
 * for MySQL, MariaDb Server.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new ColumnSchemaBuilder(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
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
     * @throws Exception
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
