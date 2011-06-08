CREATE TABLE IF NOT EXISTS `{prefix}kick` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `kickerid` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `length` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `roomId` (`roomId`),
  KEY `time` (`time`),
  KEY `length` (`length`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8;
