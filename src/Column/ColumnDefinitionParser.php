<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 *
 * @psalm-type ExtraInfo = array{
 *     characterSet?: string,
 *     check?: string,
 *     collate?: string,
 *     comment?: string,
 *     defaultValueRaw?: string,
 *     extra?: string,
 *     notNull?: bool,
 *     unique?: bool,
 *     unsigned?: bool
 * }
 */
final class ColumnDefinitionParser extends \Yiisoft\Db\Syntax\ColumnDefinitionParser
{
    /**
     * @psalm-return ExtraInfo
     * @psalm-suppress ImplementedReturnTypeMismatch, InvalidReturnType, InvalidReturnStatement
     */
    protected function extraInfo(string $extra): array
    {
        $info = parent::extraInfo($extra);

        if (empty($info['extra'])) {
            return $info;
        }

        $extra = $this->parseStringValue($info['extra'], '/\s*\b(?:CHARACTER SET|CHARSET)\s+(\S+)/i', 'characterSet', $info);

        /** @psalm-var ExtraInfo $info */
        if (!empty($extra)) {
            $info['extra'] = $extra;
        } else {
            unset($info['extra']);
        }

        return $info;
    }
}
