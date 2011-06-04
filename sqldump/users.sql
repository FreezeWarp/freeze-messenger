CREATE TABLE IF NOT EXISTS `{prefix}users` (
  `userid` int(10) NOT NULL,
  `settings` int(10) NOT NULL DEFAULT '32',
  `defaultRoom` int(10) NOT NULL DEFAULT '0',
  `favRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1',
  `watchRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `status` int(3) NOT NULL,
  `defaultFormatting` int(10) NOT NULL,
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultColour` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE={engine} DEFAULT CHARSET=utf8;