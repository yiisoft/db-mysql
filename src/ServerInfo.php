<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Driver\Pdo\PdoServerInfo;

final class ServerInfo extends PdoServerInfo
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $timezone;

    public function getTimezone(bool $refresh = false): string
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!isset($this->timezone) || $refresh) {
            /** @var string */
            $this->timezone = $this->db->createCommand(
                "SELECT LPAD(TIME_FORMAT(TIMEDIFF(NOW(), UTC_TIMESTAMP), '%H:%i'), 6, '+')",
            )->queryScalar();
        }

        return $this->timezone;
    }
}
