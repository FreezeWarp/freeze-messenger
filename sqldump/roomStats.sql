CREATE TABLE IF NOT EXISTS `{prefix}roomStats` (
  `userId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `messages` int(10) NOT NULL,
  PRIMARY KEY (`userId`,`roomId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
