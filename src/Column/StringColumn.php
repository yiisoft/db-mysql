<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

/**
 * Represents the metadata for a string column.
 */
final class StringColumn extends \Yiisoft\Db\Schema\Column\StringColumn
{
    /**
     * @var string|null The column character set.
     */
    protected ?string $characterSet = null;

    /**
     * Sets the character set for the column.
     */
    public function characterSet(?string $characterSet): static
    {
        $this->characterSet = $characterSet;
        return $this;
    }

    /**
     * Returns the character set of the column.
     *
     * @psalm-mutation-free
     */
    public function getCharacterSet(): ?string
    {
        return $this->characterSet;
    }
}
