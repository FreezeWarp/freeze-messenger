CREATE TABLE IF NOT EXISTS `{prefix}messagesCached` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `messageId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `userId` int(10) NOT NULL,
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userGroup` int(10) NOT NULL DEFAULT 1,
  `allGroups` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;