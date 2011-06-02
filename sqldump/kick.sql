CREATE TABLE IF NOT EXISTS `{prefix}kick` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `kickerid` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `length` bigint(20) NOT NULL,
  `room` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `room` (`room`),
  KEY `time` (`time`),
  KEY `length` (`length`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8;
