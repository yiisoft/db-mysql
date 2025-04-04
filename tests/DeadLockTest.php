<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use ErrorException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Transaction\TransactionInterface;

use function date;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function function_exists;
use function implode;
use function is_file;
use function microtime;
use function pcntl_fork;
use function pcntl_signal;
use function pcntl_sigtimedwait;
use function pcntl_wait;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function posix_getpid;
use function posix_kill;
use function round;
use function set_error_handler;
use function sleep;
use function sys_get_temp_dir;
use function unlink;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DeadLockTest extends TestCase
{
    use TestTrait;

    private const CHILD_EXIT_CODE_DEADLOCK = 15;
    private string $logFile = '';

    /**
     * Test deadlock exception.
     *
     * Accident deadlock exception lost while rolling back a transaction or savepoint
     *
     * {@link https://github.com/yiisoft/yii2/issues/12715}
     * {@link https://github.com/yiisoft/yii2/pull/13346}
     */
    public function testDeadlockException(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork() is not available');
        }

        if (!function_exists('posix_kill')) {
            $this->markTestSkipped('posix_kill() is not available');
        }

        if (!function_exists('pcntl_sigtimedwait')) {
            $this->markTestSkipped('pcntl_sigtimedwait() is not available');
        }

        $this->setLogFile(sys_get_temp_dir() . '/deadlock_' . posix_getpid());

        $this->deleteLog();

        try {
            /**
             * to cause deadlock we do:
             *
             * 1. FIRST errornously forgot "FOR UPDATE" while read the row for next update.
             * 2. SECOND does update the row and locks it exclusively.
             * 3. FIRST tryes to update the row too, but it already has shared lock. Here comes deadlock.
             * FIRST child will send the signal to the SECOND child.
             * So, SECOND child should be forked at first to obtain its PID.
             */
            $pidSecond = pcntl_fork();

            if (-1 === $pidSecond) {
                $this->markTestIncomplete('cannot fork');
            }

            if (0 === $pidSecond) {
                /* SECOND child */
                $this->setErrorHandler();

                exit($this->childrenUpdateLocked());
            }

            $pidFirst = pcntl_fork();

            if (-1 === $pidFirst) {
                $this->markTestIncomplete('cannot fork second child');
            }

            if (0 === $pidFirst) {
                /* FIRST child */
                $this->setErrorHandler();

                exit($this->childrenSelectAndAccidentUpdate($pidSecond));
            }

            /**
             * PARENT
             * nothing to do
             */
        } catch (\Exception|Throwable $e) {
            /* wait all children */
            while (-1 !== pcntl_wait($status)) {
                /* nothing to do */
            }

            $this->deleteLog();

            throw $e;
        }

        /**
         * wait all children all must exit with success
         */
        $errors = [];
        $deadlockHitCount = 0;

        while (-1 !== pcntl_wait($status)) {
            if (!pcntl_wifexited($status)) {
                $errors[] = 'child did not exit itself';
            } else {
                $exitStatus = pcntl_wexitstatus($status);
                if (self::CHILD_EXIT_CODE_DEADLOCK === $exitStatus) {
                    $deadlockHitCount++;
                } elseif (0 !== $exitStatus) {
                    $errors[] = 'child exited with error status';
                }
            }
        }

        $logContent = $this->getLogContentAndDelete();

        if ($errors) {
            $this->fail(
                implode('; ', $errors)
                . ($logContent ? ". Shared children log:\n$logContent" : '')
            );
        }

        $this->assertEquals(
            1,
            $deadlockHitCount,
            "exactly one child must hit deadlock; shared children log:\n" . $logContent
        );
    }

    /**
     * Main body of first child process.
     *
     * First child initializes test row and runs two nested {@see ConnectionInterface::transaction()} to perform
     * following operations:
     * 1. `SELECT ... LOCK IN SHARE MODE` the test row with shared lock instead of needed exclusive lock.
     * 2. Send signal to SECOND child identified by PID {@see $pidSecond}.
     * 3. Waits few seconds.
     * 4. `UPDATE` the test row.
     *
     * @return int Exit code. In case of deadlock exit code is {@see CHILD_EXIT_CODE_DEADLOCK}. In case of success exit
     * code is 0. Other codes means an error.
     */
    private function childrenSelectAndAccidentUpdate(int $pidSecond): int
    {
        try {
            $this->log('child 1: connect');

            $first = $this->getConnection();

            $this->log('child 1: delete');

            $first->createCommand()
                ->delete('{{customer}}', ['id' => 97])
                ->execute();

            $this->log('child 1: insert');

            /* insert test row */
            $first->createCommand()
                ->insert('{{customer}}', [
                    'id' => 97,
                    'email' => 'deadlock@example.com',
                    'name' => 'test',
                    'address' => 'test address',
                ])
                ->execute();

            $this->log('child 1: transaction');

            $first->transaction(function (ConnectionInterface $first) use ($pidSecond) {
                $first->transaction(function (ConnectionInterface $first) use ($pidSecond) {
                    $this->log('child 1: select');

                    /* SELECT with shared lock */
                    $first->createCommand('SELECT id FROM {{customer}} WHERE id = 97 LOCK IN SHARE MODE')
                        ->execute();

                    $this->log('child 1: send signal to child 2');

                    /* let child to continue */
                    if (!posix_kill($pidSecond, SIGUSR1)) {
                        throw new RuntimeException('Cannot send signal');
                    }

                    /**
                     * Now child 2 tries to do the 2nd update, and hits the lock and waits delay to let child hit the
                     * lock.
                     */

                    sleep(2);

                    $this->log('child 1: update');

                    /* now do the 3rd update for deadlock */
                    $first->createCommand()
                        ->update('{{customer}}', ['name' => 'first'], ['id' => 97])
                        ->execute();

                    $this->log('child 1: commit');
                });
            }, TransactionInterface::REPEATABLE_READ);
        } catch (Exception $e) {
            $this->assertIsArray($e->errorInfo);
            [$sqlError, $driverError, $driverMessage] = $e->errorInfo;

            /* Deadlock found when trying to get lock; try restarting transaction */
            if ('40001' === $sqlError && 1213 === $driverError) {
                return self::CHILD_EXIT_CODE_DEADLOCK;
            }

            $this->log("child 1: ! sql error $sqlError: $driverError: $driverMessage");

            return 1;
        } catch (\Exception|Throwable $e) {
            $this->log(
                'child 1: ! exit <<' . $e::class . ' #' . $e->getCode() . ': ' . $e->getMessage() . "\n"
                . $e->getTraceAsString() . '>>'
            );

            return 1;
        }

        $this->log('child 1: exit');

        return 0;
    }

    /**
     * Main body of second child process.
     *
     * Second child at first will wait the signal from the first child in some seconds.
     *
     * After receiving the signal it runs two nested {@see ConnectionInterface::transaction()} to perform `UPDATE` with
     * the test row.
     *
     * @return int Exit code. In case of deadlock exit code is {@see CHILD_EXIT_CODE_DEADLOCK}. In case of success exit
     * code is 0. Other codes means an error.
     */
    private function childrenUpdateLocked(): int
    {
        /* install no-op signal handler to prevent termination */
        if (
            !pcntl_signal(SIGUSR1, static function () {
            }, false)
        ) {
            $this->log('child 2: cannot install signal handler');

            return 1;
        }

        try {
            /* at first, parent should do 1st select */
            $this->log('child 2: wait signal from child 1');

            if (pcntl_sigtimedwait([SIGUSR1], $info, 10) <= 0) {
                $this->log('child 2: wait timeout exceeded');

                return 1;
            }

            $this->log('child 2: connect');

            /* @var ConnectionInteface $second */
            $second = $this->getConnection(true);
            $second->open();

            /* sleep(1); */
            $this->log('child 2: transaction');

            $second->transaction(function (ConnectionInterface $second) {
                $second->transaction(function (ConnectionInterface $second) {
                    $this->log('child 2: update');
                    /* do the 2nd update */
                    $second->createCommand()
                        ->update('{{customer}}', ['name' => 'second'], ['id' => 97])
                        ->execute();

                    $this->log('child 2: commit');
                });
            }, TransactionInterface::REPEATABLE_READ);
        } catch (Exception $e) {
            $this->assertIsArray($e->errorInfo);
            [$sqlError, $driverError, $driverMessage] = $e->errorInfo;

            /* Deadlock found when trying to get lock; try restarting transaction */
            if ('40001' === $sqlError && 1213 === $driverError) {
                return self::CHILD_EXIT_CODE_DEADLOCK;
            }

            $this->log("child 2: ! sql error $sqlError: $driverError: $driverMessage");

            return 1;
        } catch (\Exception|Throwable $e) {
            $this->log(
                'child 2: ! exit <<' . $e::class . ' #' . $e->getCode() . ': ' . $e->getMessage() . "\n"
                . $e->getTraceAsString() . '>>'
            );

            return 1;
        }

        $this->log('child 2: exit');

        return 0;
    }

    /**
     * Set own error handler.
     *
     * In case of error in child process its execution bubbles up to phpunit to continue all the rest tests. So, all
     * the rest tests in this case will run both in the child and parent processes. Such mess must be prevented with
     * child's own error handler.
     *
     * @throws ErrorException
     */
    private function setErrorHandler(): void
    {
        set_error_handler(static function ($errno, $errstr, $errfile, $errline): never {
            throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });
    }

    /**
     * Sets filename for log file shared between children processes.
     */
    private function setLogFile(string $filename): void
    {
        $this->logFile = $filename;
    }

    /**
     * Deletes shared log file.
     *
     * Deletes the file {@see logFile} if it exists.
     */
    private function deleteLog(): void
    {
        /** @psalm-suppress RedundantCondition */
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    /**
     * Reads shared log content and deletes the log file.
     *
     * Reads content of log file {@see logFile} and returns it deleting the file.
     *
     * @return string|null String content of the file {@see logFile}. `false` is returned when file cannot be read.
     * `null` is returned when file does not exist or {@see logFile} is not set.
     */
    private function getLogContentAndDelete(): ?string
    {
        if (is_file($this->logFile)) {
            $content = file_get_contents($this->logFile);

            unlink($this->logFile);

            return $content;
        }

        return null;
    }

    /**
     * Append message to shared log.
     *
     * @param string $message Message to append to the log. The message will be prepended with timestamp and appended
     * with new line.
     */
    private function log(string $message): void
    {
        $time = microtime(true);
        $timeInt = floor($time);
        $timeFrac = $time - $timeInt;
        $timestamp = date('Y-m-d H:i:s', (int) $timeInt) . '.' . round($timeFrac * 1000);

        file_put_contents($this->logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    }
}
