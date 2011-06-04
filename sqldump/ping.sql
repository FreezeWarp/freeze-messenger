CREATE TABLE IF NOT EXISTS `{prefix}ping` (
  `userId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('away','busy','available','invisible','offline','') NOT NULL DEFAULT '',
  `typing` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`userId`,`roomId`),
  KEY `time` (`time`),
  KEY `userId` (`userId`),
  KEY `roomId` (`roomId`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;