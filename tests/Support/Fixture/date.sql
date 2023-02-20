DROP TABLE IF EXISTS `date`;
DROP TABLE IF EXISTS `date_default`;
DROP TABLE IF EXISTS `date_default_expressions`;

CREATE TABLE `date` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `Mydate1` date DEFAULT NULL,
    `Mydate2` date,
    `Mydatetime1` datetime DEFAULT NULL,
    `Mydatetime2` datetime,
    `Mytimestamp1` timestamp NULL DEFAULT NULL,
    `Mytimestamp2` timestamp NULL,
    `Mytime1` time DEFAULT NULL,
    `Mytime2` time,
    `Myyear1` year(4) DEFAULT NULL,
    `Myyear2` year(4),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `date_default` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `Mydate` date DEFAULT '2023-01-01',
    `Mydatetime` datetime DEFAULT '2023-01-01 00:00:00',
    `Mytimestamp` timestamp NULL DEFAULT '2023-01-01 00:00:00',
    `Mytime` time DEFAULT '12:00:00',
    `Myyear` year(4) DEFAULT '2023',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `date_default_expressions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `Mydate` date DEFAULT (CURRENT_DATE + INTERVAL 2 YEAR),
    `Mydatetime` datetime DEFAULT CURRENT_TIMESTAMP,
    `Mytimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Mytime` time DEFAULT (CURTIME()),
    `Myyear` year(4) DEFAULT (YEAR(CURRENT_TIMESTAMP)),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
