CREATE TABLE IF NOT EXISTS `{prefix}censorWords` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `listid` int(10) NOT NULL,
  `word` varchar(1000) NOT NULL,
  `severity` enum('replace','warn','confirm','block') NOT NULL DEFAULT 'replace',
  `param` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
