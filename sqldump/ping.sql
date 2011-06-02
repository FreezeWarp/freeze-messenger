CREATE TABLE IF NOT EXISTS `{prefix}ping` (
  `userid` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('away','busy','available','invisible','offline','') NOT NULL,
  `typing` enum('1','0') NOT NULL,
  PRIMARY KEY (`userid`,`roomid`),
  KEY `time` (`time`),
  KEY `userid` (`userid`),
  KEY `roomid` (`roomid`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;