<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

/**
 * @group mysql
 */
final class QuoterTest extends TestCase
{
    public function testQuoterEscapingValueFull()
    {
        $template = 'aaaaa{1}aaa{1}aaaabbbbb{2}bbbb{2}bbbb';

        $db = $this->getConnection(true);
        $quoter = $db->getQuoter();

        $db->createCommand('delete from {{quoter}}')->execute();

        for ($symbol1 = 1; $symbol1 <= 127; $symbol1++) {
            for ($symbol2 = 1; $symbol2 <= 127; $symbol2++) {
                $quotedName = $quoter->quoteValue('test_' . $symbol1 . '_' . $symbol2);
                $testString = str_replace(['{1}', '{2}',], [chr($symbol1), chr($symbol2)], $template);

                $quoteValue = $quoter->quoteValue($testString);

                $db->createCommand('insert into {{quoter}}([[name]], [[description]]) values(' . $quotedName . ', ' . $quoteValue . ')')->execute();
                $result = $db->createCommand('select * from {{quoter}} where [[name]]=' . $quotedName)->queryOne();
                $this->assertEquals($testString, $result['description']);
            }
        }
    }
}
