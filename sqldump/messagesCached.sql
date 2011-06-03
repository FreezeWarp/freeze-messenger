CREATE TABLE IF NOT EXISTS `{prefix}messagesCached` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `messageid` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `username` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usergroup` int(10) NOT NULL,
  `groupFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `groupFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;