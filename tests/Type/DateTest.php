<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Type;

use DateTime;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
* @link https://dev.mysql.com/doc/refman/8.0/en/date-and-time-types.html
 */
final class DateTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('date.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('date_default');

        $this->assertSame('date', $tableSchema->getColumn('Mydate')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydate')->getPhpType());
        $this->assertSame('datetime', $tableSchema->getColumn('Mydatetime')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydatetime')->getPhpType());
        $this->assertSame('timestamp', $tableSchema->getColumn('Mytimestamp')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mytimestamp')->getPhpType());
        $this->assertSame('time', $tableSchema->getColumn('Mytime')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mytime')->getPhpType());

        if ($db->getName() === 'mysql' && $db->getServerVersion() >= '8.0.0') {
            $this->assertSame('year', $tableSchema->getColumn('Myyear')->getDbType());
        } else {
            $this->assertSame('year(4)', $tableSchema->getColumn('Myyear')->getDbType());
        }

        $command = $db->createCommand();
        $command->insert('date_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate' => '2023-01-01',
                'Mydatetime' => '2023-01-01 00:00:00',
                'Mytimestamp' => '2023-01-01 00:00:00',
                'Mytime' => '12:00:00',
                'Myyear' => '2023',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM date_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testDefaultValueExpressions(): void
    {
        $this->setFixture('date.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('date_default_expressions', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate' => date('Y-m-d', strtotime(date('Y-m-d') . "2 year")),
                'Myyear' => date('Y'),
            ],
            $command->setSql(
                <<<SQL
                SELECT id, Mydate, Myyear FROM date_default_expressions WHERE id = 1
                SQL
            )->queryOne()
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $command->setSql(
                    <<<SQL
                    SELECT Mydatetime FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $command->setSql(
                    <<<SQL
                    SELECT Mytimestamp FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'H:i:s',
                $command->setSql(
                    <<<SQL
                    SELECT Mytime FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
    }

    public function testValue(): void
    {
        $this->setFixture('date.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('date', [
            'Mydate1' => '2007-05-08',
            'Mydate2' => null,
            'Mydatetime1' => '2007-05-08 12:35:29.123',
            'Mydatetime2' => null,
            'Mytimestamp1' => '2007-05-08 12:35:29.123',
            'Mytimestamp2' => null,
            'Mytime1' => '12:35:29.1234567',
            'Mytime2' => null,
            'Myyear1' => '2023',
            'Myyear2' => null,
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => '2007-05-08',
                'Mydate2' => null,
                'Mydatetime1' => '2007-05-08 12:35:29',
                'Mydatetime2' => null,
                'Mytimestamp1' => '2007-05-08 12:35:29',
                'Mytimestamp2' => null,
                'Mytime1' => '12:35:29',
                'Mytime2' => null,
                'Myyear1' => '2023',
                'Myyear2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM date WHERE id = 1
                SQL
            )->queryOne(),
        );
    }

    public function testValueException(): void
    {
        $this->setFixture('date.sql');

        $db = $this->getConnection(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid datetime format: 1292 Incorrect date value: '0'");
        $db->createCommand()->insert('date', ['Mydate1' => '0'])->execute();
    }
}
