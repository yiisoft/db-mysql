DROP TABLE IF EXISTS `date_default_expressions80`;

CREATE TABLE `date_default_expressions80` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `Mydate` date DEFAULT (CURRENT_DATE + INTERVAL 2 YEAR),
    `Mydatetime` datetime DEFAULT CURRENT_TIMESTAMP,
    `Mytimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Mytime` time DEFAULT (CURTIME()),
    `Myyear` year(4) DEFAULT (YEAR(CURRENT_TIMESTAMP)),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
