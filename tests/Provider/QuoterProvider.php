<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

final class QuoterProvider extends \Yiisoft\Db\Tests\Provider\QuoterProvider
{
    /**
     * @return string[][]
     */
    public static function columnNames(): array
    {
        return [
            ['*', '*'],
            ['table.*', '`table`.*'],
            ['`table`.*', '`table`.*'],
            ['table.column', '`table`.`column`'],
            ['`table`.column', '`table`.`column`'],
            ['table.`column`', '`table`.`column`'],
            ['`table`.`column`', '`table`.`column`'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleColumnNames(): array
    {
        return [
            ['test', '`test`', 'test'],
            ['`test`', '`test`', 'test'],
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleTableNames(): array
    {
        return [
            ['test', 'test', ],
            ['te\'st', 'te\'st', ],
            ['te"st', 'te"st', ],
            ['current-table-name', 'current-table-name', ],
            ['`current-table-name`', 'current-table-name', ],
        ];
    }

    public static function tableNameParts(): array
    {
        return [
            ['', ''],
            ['[]', '[]'],
            ['animal', 'animal'],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', '[animal]', '[dbo]'],
            ['[other].[animal2]', '[animal2]', '[other]'],
            ['other.[animal2]', '[animal2]', 'other'],
            ['other.animal2', 'animal2', 'other'],
        ];
    }
}
