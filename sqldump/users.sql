CREATE TABLE IF NOT EXISTS `{prefix}users` (
  `userId` int(10) NOT NULL,
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userGroup` int(10) NOT NULL DEFAULT 1
  `allGroups` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultRoom` int(10) NOT NULL DEFAULT 1,
  `favRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1',
  `watchRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `status` int(3) NOT NULL,
  `defaultFormatting` int(10) NOT NULL,
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultColour` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `settings` int(10) NOT NULL DEFAULT 0,
  `settingsOfficialAjax` int(10) NOT NULL DEFAULT 8192,
  `userPrivs` int(10) NOT NULL,
  `adminPrivs` int(10) NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;