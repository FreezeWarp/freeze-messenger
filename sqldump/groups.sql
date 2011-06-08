CREATE TABLE IF NOT EXISTS `{prefix}groups` (
  `groupId` int(10) NOT NULL,
  `groupName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `memberIds` int(10) NOT NULL DEFAULT 1,
  `userFormatStart` CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatEnd` CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;