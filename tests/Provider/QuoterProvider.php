<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Provider;

use Yiisoft\Db\Tests\Provider\AbstractQuoterProvider;

final class QuoterProvider extends AbstractQuoterProvider
{
    /**
     * @return string[][]
     */
    public function columnNames(): array
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
    public function simpleColumnNames(): array
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
    public function simpleTableNames(): array
    {
        return [
            ['test', 'test', ],
            ['te\'st', 'te\'st', ],
            ['te"st', 'te"st', ],
            ['current-table-name', 'current-table-name', ],
            ['`current-table-name`', 'current-table-name', ],
        ];
    }

    public function tableNameParts(): array
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
