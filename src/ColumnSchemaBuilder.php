<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

/**
 * The class ColumnSchemaBuilder for Mysql database.
 */
final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    private ConnectionInterface $db;

    /**
     *  @param array|int|string|null $length column size or precision definition.
     */
    public function __construct(string $type, $length, ConnectionInterface $db)
    {
        $this->db = $db;

        parent::__construct($type, $length);
    }

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
     * Builds the after constraint for the column. Defaults to unsupported.
     *
     * @return string a string containing the AFTER constraint.
     */
    protected function buildAfterString(): string
    {
        /** @var Connection $db */
        $db = $this->db;

        return $this->getAfter() !== null ? ' AFTER ' . $db->quoteColumnName((string) $this->getAfter()) : '';
    }

    /**
     * Builds the first constraint for the column. Defaults to unsupported.
     *
     * @return string a string containing the FIRST constraint.
     */
    protected function buildFirstString(): string
    {
        return $this->isFirst() ? ' FIRST' : '';
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
        /** @var Connection $db */
        $db = $this->db;

        return $this->getComment() !== null ? ' COMMENT ' . $db->quoteValue((string) $this->getComment()) : '';
    }

    public function __toString(): string
    {
        switch ($this->getTypeCategory()) {
            case self::CATEGORY_PK:
                $format = '{type}{length}{comment}{check}{append}{pos}';
                break;
            case self::CATEGORY_NUMERIC:
                $format = '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{pos}{check}';
                break;
            default:
                $format = '{type}{length}{notnull}{default}{unique}{comment}{append}{pos}{check}';
        }

        return $this->buildCompleteString($format);
    }
}
