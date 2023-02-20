DROP TABLE IF EXISTS `date_default_expressions57`;

CREATE TABLE `date_default_expressions57` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `Mydatetime` datetime DEFAULT CURRENT_TIMESTAMP,
    `Mytimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
