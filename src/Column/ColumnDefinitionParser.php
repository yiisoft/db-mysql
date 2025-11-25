<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Column;

use Yiisoft\Db\Syntax\AbstractColumnDefinitionParser;

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
final class ColumnDefinitionParser extends AbstractColumnDefinitionParser
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

    protected function parseTypeParams(string $type, string $params): array
    {
        return match ($type) {
            'bit',
            'bigint',
            'binary',
            'char',
            'decimal',
            'double',
            'float',
            'int',
            'integer',
            'mediumint',
            'numeric',
            'real',
            'smallint',
            'string',
            'tinyint',
            'varbinary',
            'varchar',
            'year' => $this->parseSizeInfo($params),
            'enum' => $this->parseEnumValues($params),
            default => [],
        };
    }

    /**
     * @psalm-return array{enumValues: list<string>}
     */
    protected function parseEnumValues(string $params): array
    {
        preg_match_all("/'([^']*)'/", $params, $matches);

        return ['enumValues' => $matches[1]];
    }
}
